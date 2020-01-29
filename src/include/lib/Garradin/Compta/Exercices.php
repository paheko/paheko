<?php

namespace Garradin\Compta;

use Garradin\Entities\Compta\Exercice;
use KD2\DB\EntityManager;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Exercices
{
    public function listOpen()
    {
        return EntityManager::getInstance(Exercice::class)->all('SELECT * FROM @TABLE WHERE cloture = 0;');
    }

    public function getCurrent()
    {
        return EntityManager::findOne(Exercice::class, 'SELECT * FROM @TABLE ORDER BY fin DESC LIMIT 1');
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
            INNER JOIN compta_journal ON 
                compta_journal.id_exercice = :id AND (
                    (compta_comptes.id = compta_journal.compte_debit AND CAST(substr(compta_journal.compte_debit, 1, 1) AS INTEGER) <= 5)
                    OR (compta_comptes.id = compta_journal.compte_credit AND CAST(substr(compta_journal.compte_credit, 1, 1) AS INTEGER) <= 5)
                )
            WHERE solde != 0
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

            $compte_debit = $solde < 0 ? 890 : $row['compte'];
            $compte_credit = $solde > 0 ? 890 : $row['compte'];

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
        $resultat = $rapports->compteResultat(['id_exercice' => $exercice], [6, 7]);
        $resultat = $resultat['resultat'];

        if ($resultat != 0)
        {
            $journal = new Journal;
            $journal->add([
                'libelle'   =>  'Résultat de l\'exercice précédent',
                'date'      =>  $date,
                'montant'   =>  abs($resultat),
                'compte_debit'  =>  $resultat < 0 ? $resultat_debiteur : 890,
                'compte_credit' =>  $resultat > 0 ? $resultat_excedent : 890,
            ]);
        }

        return true;
    }
}
