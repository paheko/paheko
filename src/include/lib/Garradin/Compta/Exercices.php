<?php

namespace Garradin\Compta;

use \Garradin\DB;
use \Garradin\Utils;
use \Garradin\UserException;

class Exercices
{
    public function add($data)
    {
        $this->_checkFields($data);

        $db = DB::getInstance();

        if ($db->firstColumn('SELECT 1 FROM compta_exercices WHERE
            (debut <= :debut AND fin >= :debut) OR (debut <= :fin AND fin >= :fin);',
            ['debut' => $data['debut'], 'fin' => $data['fin']]))
        {
            throw new UserException('La date de début ou de fin se recoupe avec un autre exercice.');
        }

        if ($db->firstColumn('SELECT 1 FROM compta_exercices WHERE cloture = 0;'))
        {
            throw new UserException('Il n\'est pas possible de créer un nouvel exercice tant qu\'il existe un exercice non-clôturé.');
        }

        $db->insert('compta_exercices', [
            'libelle'   =>  trim($data['libelle']),
            'debut'     =>  $data['debut'],
            'fin'       =>  $data['fin'],
        ]);

        return $db->lastInsertRowId();
    }

    public function edit($id, $data)
    {
        $db = DB::getInstance();

        $this->_checkFields($data);

        // Evitons que les exercices se croisent
        if ($db->firstColumn('SELECT 1 FROM compta_exercices WHERE id != :id AND
            ((debut <= :debut AND fin >= :debut) OR (debut <= :fin AND fin >= :fin));',
            ['debut' => $data['debut'], 'fin' => $data['fin'], 'id' => (int) $id]))
        {
            throw new UserException('La date de début ou de fin se recoupe avec un autre exercice.');
        }

        // On vérifie qu'on ne va pas mettre des opérations en dehors de tout exercice
        if ($db->firstColumn('SELECT 1 FROM compta_journal WHERE id_exercice = ?
            AND date < ? LIMIT 1;', (int)$id, $data['debut']))
        {
            throw new UserException('Des opérations de cet exercice ont une date antérieure à la date de début de l\'exercice.');
        }

        if ($db->firstColumn('SELECT 1 FROM compta_journal WHERE id_exercice = ?
            AND date > ? LIMIT 1;', (int)$id, $data['fin']))
        {
            throw new UserException('Des opérations de cet exercice ont une date postérieure à la date de fin de l\'exercice.');
        }

        $db->update('compta_exercices', [
            'libelle'   =>  trim($data['libelle']),
            'debut'     =>  $data['debut'],
            'fin'       =>  $data['fin'],
        ], 'id = :id', ['id' => (int)$id]);

        return true;
    }

    /**
     * Clôturer un exercice et en ouvrir un nouveau
     * Le report à nouveau n'est pas effectué automatiquement par cette fonction, voir doReports pour ça.
     * @param  integer  $id     ID de l'exercice à clôturer
     * @param  string   $end    Date de clôture de l'exercice au format Y-m-d
     * @return integer          L'ID du nouvel exercice créé
     */
    public function close($id, $end)
    {
        $db = DB::getInstance();

        if (!Utils::checkDate($end))
        {
            throw new UserException('Date de fin vide ou invalide.');
        }

        $db->begin();

        // Clôture de l'exercice
        $db->update('compta_exercices', [
            'cloture'   =>  1,
            'fin'       =>  $end,
        ], 'id = :id', ['id' => (int)$id]);

        // Date de début du nouvel exercice : lendemain de la clôture du précédent exercice
        $new_begin = Utils::modifyDate($end, '+1 day');

        // Date de fin du nouvel exercice : un an moins un jour après l'ouverture
        $new_end = Utils::modifyDate($new_begin, '+1 year -1 day');

        // Enfin sauf s'il existe déjà des opérations après cette date, auquel cas la date de fin
        // est fixée à la date de la dernière opération, ceci pour ne pas avoir d'opération
        // orpheline d'exercice
        $last = $db->firstColumn('SELECT date FROM compta_journal WHERE id_exercice = ? AND date >= ? ORDER BY date DESC LIMIT 1;', $id, $new_end);
        $new_end = $last ?: $new_end;

        // Création du nouvel exercice
        $new_id = $this->add([
            'debut'     =>  $new_begin,
            'fin'       =>  $new_end,
            'libelle'   =>  'Nouvel exercice'
        ]);

        // Ré-attribution des opérations de l'exercice à clôturer qui ne sont pas dans son
        // intervale au nouvel exercice
        $db->update('compta_journal', ['id_exercice' => $new_id], 'id_exercice = :id AND date >= :date', [
            'id'   => $id,
            'date' => $new_begin,
        ]);

        $db->commit();

        return $new_id;
    }

    /**
     * Créer les reports à nouveau issus de l'exercice $old_id dans le nouvel exercice courant
     * @param  integer $old_id  ID de l'ancien exercice
     * @param  integer $new_id  ID du nouvel exercice
     * @param  string  $date    Date Y-m-d donnée aux opérations créées
     * @return boolean          true si succès
     */
    public function doReports($old_id, $date)
    {
        $db = DB::getInstance();

        $db->begin();

        $report_crediteur = 110;
        $report_debiteur  = 119;

        $comptes = new Comptes;

        if (!$comptes->isActive($report_crediteur))
        {
            throw new UserException('Impossible de faire le report à nouveau : le compte de report créditeur ' . $report_crediteur . ' n\'existe pas ou est désactivé.');
        }
        else if (!$comptes->isActive($report_debiteur))
        {
            throw new UserException('Impossible de faire le report à nouveau : le compte de report débiteur ' . $report_debiteur . ' n\'existe pas ou est désactivé.');
        }

        unset($comptes);

        $this->solderResultat($old_id, $date);

        // Récupérer chacun des comptes de bilan et leurs soldes (uniquement les classes 1 à 5)
        $statement = $db->preparedQuery('SELECT compta_comptes.id AS compte, compta_comptes.position AS position,
            ROUND(COALESCE((SELECT SUM(montant) FROM compta_journal WHERE compte_debit = compta_comptes.id AND id_exercice = :id), 0), 2)
            - ROUND(COALESCE((SELECT SUM(montant) FROM compta_journal WHERE compte_credit = compta_comptes.id AND id_exercice = :id), 0), 2) AS solde
            FROM compta_comptes 
            INNER JOIN compta_journal ON compta_comptes.id = compta_journal.compte_debit 
                OR compta_comptes.id = compta_journal.compte_credit
            WHERE id_exercice = :id AND solde != 0 AND CAST(substr(compta_comptes.id, 1, 1) AS INTEGER) <= 5
            GROUP BY compta_comptes.id;', ['id' => $old_id]);

        $diff = 0;
        $journal = new Journal;

        while ($row = $statement->fetchArray(SQLITE3_ASSOC))
        {
            $solde = $row['solde'];

            // Solde du compte à zéro : aucun report à faire
            if (empty($solde))
            {
                continue;
            }

            $compte_debit = $solde < 0 ? NULL : $row['compte'];
            $compte_credit = $solde > 0 ? NULL : $row['compte'];

            $diff += $solde;
            $solde = round(abs($solde), 2);

            // Chaque solde de compte est reporté dans le nouvel exercice
            $journal->add([
                'libelle'       =>  'Report à nouveau',
                'date'          =>  $date,
                'montant'       =>  $solde,
                'compte_debit'  =>  $compte_debit,
                'compte_credit' =>  $compte_credit,
                'remarques'     =>  'Report de solde créé automatiquement à la clôture de l\'exercice précédent',
            ]);
        }
        
        // FIXME utiliser $diff pour équilibrer

        $db->commit();

        return true;
    }

    /**
     * Solder les comptes de charge et de produits de l'exercice N 
     * et les inscrire au résultat de l'exercice N+1
     * @param  integer  $exercice   ID de l'exercice à solder
     * @param  string   $date       Date de début de l'exercice Y-m-d
     * @return boolean              true en cas de succès
     */
    public function solderResultat($exercice, $date)
    {
        $db = DB::getInstance();

        $resultat_excedent = 120;
        $resultat_debiteur = 129;

        $comptes = new Comptes;

        if (!$comptes->isActive($resultat_excedent))
        {
            throw new UserException('Impossible de solder l\'exercice : le compte de résultat excédent ' . $resultat_excedent . ' n\'existe pas ou est désactivé.');
        }
        else if (!$comptes->isActive($resultat_debiteur))
        {
            throw new UserException('Impossible de solder l\'exercice : le compte de résultat débiteur ' . $resultat_debiteur . ' n\'existe pas ou est désactivé.');
        }

        unset($comptes);

        $rapports = new Rapports;
        $resultat = $rapports->compteResultat(['id_exercice' => $exercice]);
        $resultat = $resultat['resultat'];

        if ($resultat != 0)
        {
            $journal = new Journal;
            $journal->add([
                'libelle'   =>  'Résultat de l\'exercice précédent',
                'date'      =>  $date,
                'montant'   =>  abs($resultat),
                'compte_debit'  =>  $resultat < 0 ? $resultat_debiteur : NULL,
                'compte_credit' =>  $resultat > 0 ? $resultat_excedent : NULL,
            ]);
        }

        return true;
    }
    
    public function delete($id)
    {
        $db = DB::getInstance();

        // Ne pas supprimer un compte qui est utilisé !
        if ($db->test('compta_journal', $db->where('id_exercice', $id)))
        {
            throw new UserException('Cet exercice ne peut être supprimé car des opérations comptables y sont liées.');
        }

        $db->delete('compta_exercices', 'id = ?', (int)$id);

        return true;
    }

    public function get($id, $with_count = false)
    {
        $with_count = $with_count
            ? ', (SELECT COUNT(*) FROM compta_journal WHERE id_exercice = compta_exercices.id) AS nb_operations'
            : '';

        $db = DB::getInstance();
        return $db->first('SELECT *, strftime(\'%s\', debut) AS debut,
            strftime(\'%s\', fin) AS fin ' . $with_count . '
            FROM compta_exercices WHERE id = ?;', (int)$id);
    }

    public function getCurrent()
    {
        $db = DB::getInstance();
        return $db->first('SELECT *, strftime(\'%s\', debut) AS debut, strftime(\'%s\', fin) AS fin FROM compta_exercices
            WHERE cloture = 0 LIMIT 1;');
    }

    public function getCurrentId()
    {
        $db = DB::getInstance();
        return $db->firstColumn('SELECT id FROM compta_exercices WHERE cloture = 0 LIMIT 1;');
    }

    public function getList()
    {
        $db = DB::getInstance();
        return $db->getGrouped('SELECT id, *, strftime(\'%s\', debut) AS debut,
            strftime(\'%s\', fin) AS fin,
            (SELECT COUNT(*) FROM compta_journal WHERE id_exercice = compta_exercices.id) AS nb_operations
            FROM compta_exercices ORDER BY fin DESC;');
    }

    protected function _checkFields(&$data)
    {
        if (empty($data['libelle']) || !trim($data['libelle']))
        {
            throw new UserException('Le libellé ne peut rester vide.');
        }

        $data['libelle'] = trim($data['libelle']);

        if (empty($data['debut']) || !checkdate(substr($data['debut'], 5, 2), substr($data['debut'], 8, 2), substr($data['debut'], 0, 4)))
        {
            throw new UserException('Date de début vide ou invalide.');
        }

        if (empty($data['fin']) || !checkdate(substr($data['fin'], 5, 2), substr($data['fin'], 8, 2), substr($data['fin'], 0, 4)))
        {
            throw new UserException('Date de fin vide ou invalide.');
        }

        return true;
    }
}
