<?php

namespace Garradin;

class Compta_Journal
{
    protected function _getCurrentExercice()
    {
        $db = DB::getInstance();
        $id = $db->querySingle('SELECT id FROM compta_exercices WHERE cloture = 0 LIMIT 1;');

        if (!$id)
        {
            throw new UserException('Aucun exercice en cours.');
        }

        return $id;
    }

    public function checkExercice()
    {
        return $this->_getCurrentExercice();
    }

    protected function _checkOpenExercice($id)
    {
        if (is_null($id))
            return true;

        $db = DB::getInstance();
        $id = $db->simpleQuerySingle('SELECT id FROM compta_exercices
            WHERE cloture = 0 AND id = ? LIMIT 1;', false, (int)$id);

        if ($id)
            return true;

        return false;
    }

    public function getSolde($id_compte, $inclure_sous_comptes = false)
    {
        $db = DB::getInstance();
        $exercice = $this->_getCurrentExercice();
        $compte = $inclure_sous_comptes
            ? 'LIKE \'' . $db->escapeString(trim($id_compte)) . '%\''
            : '= \'' . $db->escapeString(trim($id_compte)) . '\'';

        $debit = 'COALESCE((SELECT SUM(montant) FROM compta_journal WHERE compte_debit '.$compte.' AND id_exercice = '.(int)$exercice.'), 0)';
        $credit = 'COALESCE((SELECT SUM(montant) FROM compta_journal WHERE compte_credit '.$compte.' AND id_exercice = '.(int)$exercice.'), 0)';

        // L'actif augmente au débit, le passif au crédit
        $position = $db->simpleQuerySingle('SELECT position FROM compta_comptes WHERE id = ?;', false, $id_compte);

        if (($position & Compta_Comptes::ACTIF) || ($position & Compta_Comptes::CHARGE))
        {
            $query = $debit . ' - ' . $credit;
        }
        else
        {
            $query = $credit . ' - ' . $debit;
        }

        return $db->querySingle('SELECT ' . $query . ';');
    }

    public function getJournalCompte($compte, $inclure_sous_comptes = false)
    {
        $db = DB::getInstance();

        $position = $db->simpleQuerySingle('SELECT position FROM compta_comptes WHERE id = ?;', false, $compte);

        $exercice = $this->_getCurrentExercice();
        $compte = $inclure_sous_comptes
            ? 'LIKE \'' . $db->escapeString(trim($compte)) . '%\''
            : '= \'' . $db->escapeString(trim($compte)) . '\'';

        // L'actif et les charges augmentent au débit, le passif et les produits au crédit
        if (($position & Compta_Comptes::ACTIF) || ($position & Compta_Comptes::CHARGE))
        {
            $d = '';
            $c = '-';
        }
        else
        {
            $d = '-';
            $c = '';
        }

        $query = 'SELECT *, strftime(\'%s\', date) AS date,
            running_sum(CASE WHEN compte_debit '.$compte.' THEN '.$d.'montant ELSE '.$c.'montant END) AS solde
            FROM compta_journal WHERE (compte_debit '.$compte.' OR compte_credit '.$compte.') AND id_exercice = '.(int)$exercice.'
            ORDER BY date ASC;';

        // Obligatoire pour bien taper dans l'index de la date
        // sinon running_sum est appelé 2 fois et ça marche pas du coup
        // FIXME mettre ça ailleurs pour que ça soit appelé moins souvent
        $db->exec('ANALYZE compta_journal;');

        $db->resetRunningSum();
        return $db->simpleStatementFetch($query);
    }

    public function add($data)
    {
        $this->_checkFields($data);

        $db = DB::getInstance();

        $data['id_exercice'] = $this->_getCurrentExercice();

        $db->simpleInsert('compta_journal', $data);
        $id = $db->lastInsertRowId();

        return $id;
    }

    public function edit($id, $data)
    {
        $db = DB::getInstance();

        // Vérification que l'on peut éditer cette opération
        if (!$this->_checkOpenExercice($db->simpleQuerySingle('SELECT id_exercice FROM compta_journal WHERE id = ?;', false, $id)))
        {
            throw new UserException('Cette opération fait partie d\'un exercice qui a été clôturé.');
        }

        $this->_checkFields($data);

        $db->simpleUpdate('compta_journal', $data,
            'id = \''.trim($id).'\'');

        return true;
    }

    public function delete($id)
    {
        $db = DB::getInstance();

        // Vérification que l'on peut éditer cette opération
        if (!$this->_checkOpenExercice($db->simpleQuerySingle('SELECT id_exercice FROM compta_journal WHERE id = ?;', false, $id)))
        {
            throw new UserException('Cette opération fait partie d\'un exercice qui a été clôturé.');
        }

        $db->simpleExec('DELETE FROM compta_journal WHERE id = ?;', (int)$id);

        return true;
    }

    public function get($id)
    {
        $db = DB::getInstance();
        return $db->simpleQuerySingle('SELECT *, strftime(\'%s\', date) AS date FROM compta_journal WHERE id = ?;', true, $id);
    }

    public function getLinkedTransaction($id)
    {
        $db = DB::getInstance();
        return $db->simpleQuerySingle('SELECT id_membre_transaction 
            FROM membres_transactions_operations WHERE id_operation = ?;', false, (int)$id);
    }

    protected function _checkFields(&$data)
    {
        $db = DB::getInstance();

        if (empty($data['libelle']) || !trim($data['libelle']))
        {
            throw new UserException('Le libellé ne peut rester vide.');
        }

        $data['libelle'] = trim($data['libelle']);

        if (!empty($data['moyen_paiement'])
            && !$db->simpleQuerySingle('SELECT 1 FROM compta_moyens_paiement WHERE code = ?;', false, $data['moyen_paiement']))
        {
            throw new UserException('Moyen de paiement invalide.');
        }

        if (empty($data['date']) || !utils::checkDate($data['date']))
        {
            throw new UserException('Date vide ou invalide.');
        }

        if (!$db->simpleQuerySingle('SELECT 1 FROM compta_exercices WHERE cloture = 0
            AND debut <= :date AND fin >= :date;', false, array('date' => $data['date'])))
        {
            throw new UserException('La date ne correspond pas à l\'exercice en cours.');
        }

        if (empty($data['moyen_paiement']))
        {
            $data['moyen_paiement'] = null;
            $data['numero_cheque'] = null;
        }
        else
        {
            $data['moyen_paiement'] = strtoupper($data['moyen_paiement']);

            if ($data['moyen_paiement'] != 'CH')
            {
                $data['numero_cheque'] = null;
            }

            if (!$db->simpleQuerySingle('SELECT 1 FROM compta_moyens_paiement WHERE code = ? LIMIT 1;',
                false, $data['moyen_paiement']))
            {
                throw new UserException('Moyen de paiement invalide.');
            }
        }

        $data['montant'] = str_replace(',', '.', $data['montant']);
        $data['montant'] = (float)$data['montant'];

        if ($data['montant'] <= 0)
        {
            throw new UserException('Le montant ne peut être égal ou inférieur à zéro.');
        }

        foreach (array('remarques', 'numero_piece', 'numero_cheque') as $champ)
        {
            if (empty($data[$champ]) || !trim($data[$champ]))
            {
                $data[$champ] = '';
            }
            else
            {
                $data[$champ] = trim($data[$champ]);
            }
        }

        if (!array_key_exists('compte_debit', $data) || 
            (!is_null($data['compte_debit']) && 
                !$db->simpleQuerySingle('SELECT 1 FROM compta_comptes WHERE id = ?;', false, $data['compte_debit'])))
        {
            throw new UserException('Compte débité inconnu.');
        }

        if (!array_key_exists('compte_credit', $data) || 
            (!is_null($data['compte_credit']) && 
                !$db->simpleQuerySingle('SELECT 1 FROM compta_comptes WHERE id = ?;', false, $data['compte_credit'])))
        {
            throw new UserException('Compte crédité inconnu.');
        }

        $data['compte_credit'] = is_null($data['compte_credit']) ? null : strtoupper(trim($data['compte_credit']));
        $data['compte_debit'] = is_null($data['compte_debit']) ? null : strtoupper(trim($data['compte_debit']));

        if ($data['compte_credit'] == $data['compte_debit'])
        {
            throw new UserException('Compte crédité identique au compte débité.');
        }

        if (isset($data['id_categorie']))
        {
            if (!$db->simpleQuerySingle('SELECT 1 FROM compta_categories WHERE id = ?;', false, (int)$data['id_categorie']))
            {
                throw new UserException('Catégorie inconnue.');
            }

            $data['id_categorie'] = (int)$data['id_categorie'];
        }
        else
        {
            $data['id_categorie'] = NULL;
        }

        if (isset($data['id_auteur']))
        {
            $data['id_auteur'] = (int)$data['id_auteur'];
        }

        return true;
    }

    public function getListForCategory($type = null, $cat = null)
    {
        $db = DB::getInstance();
        $exercice = $this->_getCurrentExercice();

        $query = 'SELECT compta_journal.*, strftime(\'%s\', compta_journal.date) AS date ';

        if (is_null($cat) && !is_null($type))
        {
            $query.= ', compta_categories.intitule AS categorie
                FROM compta_journal LEFT JOIN compta_categories
                ON compta_journal.id_categorie = compta_categories.id ';
        }
        else
        {
            $query.= ' FROM compta_journal ';
        }

        $query .= ' WHERE ';

        if (!is_null($cat))
        {
            $query .= 'id_categorie = ' . (int)$cat;
        }
        elseif (is_null($type) && is_null($cat))
        {
            $query .= 'id_categorie IS NULL';
        }
        else
        {
            $query.= 'id_categorie IN (SELECT id FROM compta_categories WHERE type = '.(int)$type.')';
        }

        $query .= ' AND id_exercice = ' . (int)$exercice;
        $query .= ' ORDER BY date;';

        return $db->simpleStatementFetch($query);
    }

    public function searchSQL($query)
    {
        $db = DB::getInstance();

        if (!preg_match('/LIMIT\s+/', $query))
        {
            $query = preg_replace('/;?\s*$/', '', $query);
            $query .= ' LIMIT 100';
        }

        $st = $db->prepare($query);

        if (!$st->readOnly())
        {
            throw new UserException('Seules les requêtes en lecture sont autorisées.');
        }

        $res = $st->execute();
        $out = [];

        while ($row = $res->fetchArray(SQLITE3_ASSOC))
        {
            $out[] = $row;
        }

        return $out;
    }

    public function schemaSQL()
    {
        $db = DB::getInstance();

        $tables = [
            'journal'   =>  $db->querySingle('SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = \'compta_journal\';'),
        ];

        return $tables;
    }
}

?>