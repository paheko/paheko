<?php

class Garradin_Compta_Journal
{
    protected function _getCurrentExercice()
    {
        $db = Garradin_DB::getInstance();
        $id = $db->querySingle('SELECT id FROM compta_exercices
            WHERE debut <= date(\'now\') AND fin >= date(\'now\') AND clos = 0 LIMIT 1;');

        if (!$id)
            return null;

        return $id;
    }

    public function getCurrentExercice()
    {
        $db = Garradin_DB::getInstance();
        return $db->querySingle('SELECT *, strftime(\'%s\', debut) AS debut, strftime(\'%s\', fin) FROM compta_exercices
            WHERE debut <= date(\'now\') AND fin >= date(\'now\') AND clos = 0 LIMIT 1;', true);
    }

    protected function _checkOpenExercice($id)
    {
        if (is_null($id))
            return true;

        $db = Garradin_DB::getInstance();
        $id = $db->querySingle('SELECT id FROM compta_exercices
            WHERE debut <= date(\'now\') AND fin >= date(\'now\') AND clos = 0 LIMIT 1;');

        if ($id)
            return true;

        return false;
    }

    public function getSolde($compte, $inclure_sous_comptes = false)
    {
        $db = Garradin_DB::getInstance();
        $exercice = $this->_getCurrentExercice();
        $exercice = is_null($exercice) ? 'IS NULL' : '= ' . (int)$exercice;
        $compte = $inclure_sous_comptes
            ? 'LIKE \'' . $db->escapeString(trim($compte)) . '%\''
            : '= \'' . $db->escapeString(trim($compte)) . '\'';

        // FIXME utiliser compta_comptes.position pour déterminer le sens !
        $query = 'SELECT
            COALESCE((SELECT SUM(montant) FROM compta_journal
                WHERE compte_debit '.$compte.' AND id_exercice '.$exercice.'), 0)
            - COALESCE((SELECT SUM(montant) FROM compta_journal
                WHERE compte_credit '.$compte.' AND id_exercice '.$exercice.'), 0);';

        return $db->querySingle($query);
    }

    public function getJournalCompte($compte, $inclure_sous_comptes = false)
    {
        $db = Garradin_DB::getInstance();
        $exercice = $this->_getCurrentExercice();
        $exercice = is_null($exercice) ? 'IS NULL' : '= ' . (int)$exercice;
        $compte = $inclure_sous_comptes
            ? 'LIKE \'' . $db->escapeString(trim($compte)) . '%\''
            : '= \'' . $db->escapeString(trim($compte)) . '\'';

        $query = 'SELECT *, strftime(\'%s\', date) AS date FROM compta_journal WHERE
                    (compte_debit '.$compte.' OR compte_credit '.$compte.') AND id_exercice '.$exercice.'
                    ORDER BY date;';

        return $db->simpleStatementFetch($query);
    }

    public function add($data)
    {
        $this->_checkFields($data);

        $db = Garradin_DB::getInstance();

        $data['id_exercice'] = $this->_getCurrentExercice();

        $db->simpleInsert('compta_journal', $data);
        $id = $db->lastInsertRowId();

        return $id;
    }

    public function edit($id, $data)
    {
        $db = Garradin_DB::getInstance();

        // Vérification que l'on peut éditer cette opération
        if (!$this->_checkOpenExercice($db->simpleQuerySingle('SELECT id_exercice FROM compta_journal WHERE id = ?;', false, $id)))
        {
            throw new UserException('Cette opération fait partie d\'un exercice qui a été clos.');
        }

        $this->_checkFields($data);

        $db->simpleUpdate('compta_journal', $data,
            'id = \''.trim($id).'\'');

        return true;
    }

    public function delete($id)
    {
        $db = Garradin_DB::getInstance();

        // Vérification que l'on peut éditer cette opération
        if (!$this->_checkOpenExercice($db->simpleQuerySingle('SELECT id_exercice FROM compta_journal WHERE id = ?;', false, $id)))
        {
            throw new UserException('Cette opération fait partie d\'un exercice qui a été clos.');
        }

        $db->simpleExec('DELETE FROM compta_journal WHERE id = ?;', (int)$id);

        return true;
    }

    public function get($id)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleQuerySingle('SELECT *, strftime(\'%s\', date) AS date FROM compta_journal WHERE id = ?;', true, $id);
    }

    protected function _checkFields(&$data)
    {
        $db = Garradin_DB::getInstance();

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

        if (empty($data['date']) || !checkdate(substr($data['date'], 5, 2), substr($data['date'], 8, 2), substr($data['date'], 0, 4)))
        {
            throw new UserException('Date vide ou invalide.');
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

        if (empty($data['compte_debit']) ||
            !$db->simpleQuerySingle('SELECT 1 FROM compta_comptes WHERE id = ?;', false, $data['compte_debit']))
        {
            throw new UserException('Compte débité inconnu.');
        }

        if (empty($data['compte_credit']) ||
            !$db->simpleQuerySingle('SELECT 1 FROM compta_comptes WHERE id = ?;', false, $data['compte_credit']))
        {
            throw new UserException('Compte crédité inconnu.');
        }

        $data['compte_credit'] = strtoupper(trim($data['compte_credit']));
        $data['compte_debit'] = strtoupper(trim($data['compte_debit']));

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

    public function getJournal()
    {
        $db = Garradin_DB::getInstance();
        $exercice = $this->_getCurrentExercice();
        $exercice = is_null($exercice) ? 'IS NULL' : '= ' . (int)$exercice;
        $query = 'SELECT *, strftime(\'%s\', date) AS date FROM compta_journal WHERE id_exercice '.$exercice.' ORDER BY date, id;';
        return $db->simpleStatementFetch($query);
    }

    public function getGrandLivre()
    {
        $db = Garradin_DB::getInstance();
        $exercice = $this->_getCurrentExercice();
        $exercice = 'id_exercice ' . (is_null($exercice) ? 'IS NULL' : '= ' . (int)$exercice);
        $livre = array('classes' => array(), 'debit' => 0.0, 'credit' => 0.0);

        $res = $db->prepare('SELECT compte FROM
            (SELECT compte_debit AS compte FROM compta_journal WHERE '.$exercice.' GROUP BY compte_debit
                UNION
                SELECT compte_credit AS compte FROM compta_journal WHERE '.$exercice.' GROUP BY compte_credit)
            ORDER BY base64(compte) COLLATE BINARY ASC;'
            )->execute();

        while ($row = $res->fetchArray(SQLITE3_NUM))
        {
            $compte = $row[0];
            $classe = substr($compte, 0, 1);
            $parent = substr($compte, 0, 2);

            if (!array_key_exists($classe, $livre['classes']))
            {
                $livre['classes'][$classe] = array();
            }

            if (!array_key_exists($parent, $livre['classes'][$classe]))
            {
                $livre['classes'][$classe][$parent] = array(
                    'total'         =>  0.0,
                    'comptes'       =>  array(),
                );
            }

            $livre['classes'][$classe][$parent]['comptes'][$compte] = array('debit' => 0.0, 'credit' => 0.0, 'journal' => array());

            $livre['classes'][$classe][$parent]['comptes'][$compte]['journal'] = $db->simpleStatementFetch(
                'SELECT *, strftime(\'%s\', date) AS date FROM (
                    SELECT * FROM compta_journal WHERE compte_debit = :compte AND '.$exercice.'
                    UNION
                    SELECT * FROM compta_journal WHERE compte_credit = :compte AND '.$exercice.'
                    )
                ORDER BY date, numero_piece, id;', SQLITE3_ASSOC, array('compte' => $compte));

            $debit = (float) $db->simpleQuerySingle(
                'SELECT SUM(montant) FROM compta_journal WHERE compte_debit = ? AND '.$exercice.';',
                false, $compte);

            $credit = (float) $db->simpleQuerySingle(
                'SELECT SUM(montant) FROM compta_journal WHERE compte_credit = ? AND '.$exercice.';',
                false, $compte);

            $livre['classes'][$classe][$parent]['comptes'][$compte]['debit'] = $debit;
            $livre['classes'][$classe][$parent]['comptes'][$compte]['credit'] = $credit;

            $livre['classes'][$classe][$parent]['total'] += $debit;
            $livre['classes'][$classe][$parent]['total'] -= $credit;

            $livre['debit'] += $debit;
            $livre['credit'] += $credit;
        }

        $res->finalize();

        return $livre;
    }

    public function getCompteResultat()
    {
        $db = Garradin_DB::getInstance();
        $exercice = $this->_getCurrentExercice();
        $exercice = 'id_exercice ' . (is_null($exercice) ? 'IS NULL' : '= ' . (int)$exercice);

        $charges    = array('comptes' => array(), 'total' => 0.0);
        $produits   = array('comptes' => array(), 'total' => 0.0);
        $resultat   = 0.0;

        $res = $db->prepare('SELECT compte, debit, credit
            FROM
                (SELECT compte_debit AS compte, SUM(montant) AS debit, 0 AS credit FROM compta_journal WHERE '.$exercice.' GROUP BY compte_debit
                UNION
                SELECT compte_credit AS compte, 0 AS debit, SUM(montant) AS credit FROM compta_journal WHERE '.$exercice.' GROUP BY compte_credit)
            WHERE compte LIKE \'6%\' OR compte LIKE \'7%\'
            ORDER BY base64(compte) COLLATE BINARY ASC;'
            )->execute();

        while ($row = $res->fetchArray(SQLITE3_NUM))
        {
            list($compte, $debit, $credit) = $row;
            $classe = substr($compte, 0, 1);
            $parent = substr($compte, 0, 2);

            if ($classe == 6)
            {
                if (!isset($charges['comptes'][$parent]))
                {
                    $charges['comptes'][$parent] = array('comptes' => array(), 'solde' => 0.0);
                }

                $solde = $debit - $credit;

                $charges['comptes'][$parent]['comptes'][$compte] = $solde;
                $charges['total'] += $solde;
                $charges['comptes'][$parent]['solde'] += $solde;
            }
            elseif ($classe == 7)
            {
                if (!isset($produits['comptes'][$parent]))
                {
                    $produits['comptes'][$parent] = array('comptes' => array(), 'solde' => 0.0);
                }

                $solde = $credit - $debit;

                $produits['comptes'][$parent]['comptes'][$compte] = $solde;
                $produits['total'] += $solde;
                $produits['comptes'][$parent]['solde'] += $solde;
            }
        }

        $res->finalize();

        $resultat = $produits['total'] - $charges['total'];

        return array('charges' => $charges, 'produits' => $produits, 'resultat' => $resultat);
    }

    public function getListForCategory($type = null, $cat = null)
    {
        $db = Garradin_DB::getInstance();
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

        $query .= ' AND id_exercice ';
        $query .= is_null($exercice) ? 'IS NULL' : '= ' . (int)$exercice;
        $query .= ' ORDER BY date;';

        return $db->simpleStatementFetch($query);
    }
}

?>