<?php

class Garradin_Compta_Exercices
{
    public function add($data)
    {
        $this->_checkFields($data);

        $db = Garradin_DB::getInstance();

        if ($db->simpleQuerySingle('SELECT 1 FROM compta_exercices WHERE
            (debut <= :debut AND fin >= :debut) OR (debut <= :fin AND fin >= :fin);', false,
            array('debut' => $data['debut'], 'fin' => $data['fin'])))
        {
            throw new UserException('La date de début ou de fin se recoupe avec un autre exercice.');
        }

        if ($db->querySingle('SELECT 1 FROM compta_exercices WHERE cloture = 0;'))
        {
            throw new UserException('Il n\'est pas possible de créer un nouvel exercice tant qu\'il existe un exercice non-clôturé.');
        }

        $db->simpleInsert('compta_exercices', array(
            'libelle'   =>  trim($data['libelle']),
            'debut'     =>  $data['debut'],
            'fin'       =>  $data['fin'],
        ));

        return $db->lastInsertRowId();
    }

    public function edit($id, $data)
    {
        $db = Garradin_DB::getInstance();

        $this->_checkFields($data);

        // Evitons que les exercices se croisent
        if ($db->simpleQuerySingle('SELECT 1 FROM compta_exercices WHERE id != :id AND
            ((debut <= :debut AND fin >= :debut) OR (debut <= :fin AND fin >= :fin));', false,
            array('debut' => $data['debut'], 'fin' => $data['fin'], 'id' => (int) $id)))
        {
            throw new UserException('La date de début ou de fin se recoupe avec un autre exercice.');
        }

        // On vérifie qu'on ne va pas mettre des opérations en dehors de tout exercice
        if ($db->simpleQuerySingle('SELECT 1 FROM compta_journal WHERE id_exercice = ?
            AND date < ? LIMIT 1;', false, (int)$id, $data['debut']))
        {
            throw new UserException('Des opérations de cet exercice ont une date antérieure à la date de début de l\'exercice.');
        }

        if ($db->simpleQuerySingle('SELECT 1 FROM compta_journal WHERE id_exercice = ?
            AND date > ? LIMIT 1;', false, (int)$id, $data['fin']))
        {
            throw new UserException('Des opérations de cet exercice ont une date postérieure à la date de fin de l\'exercice.');
        }

        $db->simpleUpdate('compta_exercices', array(
            'libelle'   =>  trim($data['libelle']),
            'debut'     =>  $data['debut'],
            'fin'       =>  $data['fin'],
        ), 'id = \''.(int)$id.'\'');

        return true;
    }

    public function close($id)
    {
        $db = Garradin_DB::getInstance();

        $db->simpleUpdate('compta_exercices', array(
            'cloture'   =>  1,
        ), 'id = \''.(int)$id.'\'');

        return true;
    }

    public function delete($id)
    {
        $db = Garradin_DB::getInstance();

        // Ne pas supprimer un compte qui est utilisé !
        if ($db->simpleQuerySingle('SELECT 1 FROM compta_journal WHERE id_exercice = ? LIMIT 1;', false, $id))
        {
            throw new UserException('Cet exercice ne peut être supprimé car des opérations comptables y sont liées.');
        }

        $db->simpleExec('DELETE FROM compta_exercices WHERE id = ?;', (int)$id);

        return true;
    }

    public function get($id)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleQuerySingle('SELECT *, strftime(\'%s\', debut) AS debut,
            strftime(\'%s\', fin) AS fin FROM compta_exercices WHERE id = ?;', true, (int)$id);
    }

    public function getCurrent()
    {
        $db = Garradin_DB::getInstance();
        return $db->querySingle('SELECT *, strftime(\'%s\', debut) AS debut, strftime(\'%s\', fin) FROM compta_exercices
            WHERE cloture = 0 LIMIT 1;', true);
    }

    public function getList()
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleStatementFetchAssocKey('SELECT id, *, strftime(\'%s\', debut) AS debut,
            strftime(\'%s\', fin) AS fin,
            (SELECT COUNT(*) FROM compta_journal WHERE id_exercice = compta_exercices.id) AS nb_operations
            FROM compta_exercices ORDER BY fin DESC;', SQLITE3_ASSOC);
    }

    protected function _checkFields(&$data)
    {
        $db = Garradin_DB::getInstance();

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


    public function getJournal($exercice)
    {
        $db = Garradin_DB::getInstance();
        $query = 'SELECT *, strftime(\'%s\', date) AS date FROM compta_journal
            WHERE id_exercice = '.(int)$exercice.' ORDER BY date, id;';
        return $db->simpleStatementFetch($query);
    }

    public function getGrandLivre($exercice)
    {
        $db = Garradin_DB::getInstance();
        $livre = array('classes' => array(), 'debit' => 0.0, 'credit' => 0.0);

        $res = $db->prepare('SELECT compte FROM
            (SELECT compte_debit AS compte FROM compta_journal
                    WHERE id_exercice = '.(int)$exercice.' GROUP BY compte_debit
                UNION
                SELECT compte_credit AS compte FROM compta_journal
                    WHERE id_exercice = '.(int)$exercice.' GROUP BY compte_credit)
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
                    SELECT * FROM compta_journal WHERE compte_debit = :compte AND id_exercice = '.(int)$exercice.'
                    UNION
                    SELECT * FROM compta_journal WHERE compte_credit = :compte AND id_exercice = '.(int)$exercice.'
                    )
                ORDER BY date, numero_piece, id;', SQLITE3_ASSOC, array('compte' => $compte));

            $debit = (float) $db->simpleQuerySingle(
                'SELECT SUM(montant) FROM compta_journal WHERE compte_debit = ? AND id_exercice = '.(int)$exercice.';',
                false, $compte);

            $credit = (float) $db->simpleQuerySingle(
                'SELECT SUM(montant) FROM compta_journal WHERE compte_credit = ? AND id_exercice = '.(int)$exercice.';',
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

    public function getCompteResultat($exercice)
    {
        $db = Garradin_DB::getInstance();

        $charges    = array('comptes' => array(), 'total' => 0.0);
        $produits   = array('comptes' => array(), 'total' => 0.0);
        $resultat   = 0.0;

        $res = $db->prepare('SELECT compte, debit, credit
            FROM
                (SELECT compte_debit AS compte, SUM(montant) AS debit, 0 AS credit
                    FROM compta_journal WHERE id_exercice = '.(int)$exercice.' GROUP BY compte_debit
                UNION
                SELECT compte_credit AS compte, 0 AS debit, SUM(montant) AS credit
                    FROM compta_journal WHERE id_exercice = '.(int)$exercice.' GROUP BY compte_credit)
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

    public function getBilan($exercice)
    {
        $db = Garradin_DB::getInstance();

        require_once GARRADIN_ROOT . '/include/class.compta_comptes.php';
        $include = array(Garradin_Compta_Comptes::ACTIF, Garradin_Compta_Comptes::PASSIF,
            Garradin_Compta_Comptes::PASSIF | Garradin_Compta_Comptes::ACTIF);

        $actif      = array('comptes' => array(), 'total' => 0.0);
        $passif     = array('comptes' => array(), 'total' => 0.0);

        $resultat = $this->getCompteResultat($exercice);

        if ($resultat['resultat'] > 0)
        {
            $passif['comptes']['12'] = array(
                'comptes'   =>  array('120' => $resultat['resultat']),
                'solde'     =>  $resultat['resultat']
            );

            $passif['total'] = $resultat['resultat'];
        }
        else
        {
            $passif['comptes']['12'] = array(
                'comptes'   =>  array('121' => $resultat['resultat']),
                'solde'     =>  $resultat['resultat']
            );

            $passif['total'] = $resultat['resultat'];
        }

        // Y'a sûrement moyen d'améliorer tout ça pour que le maximum de travail
        // soit fait au niveau du SQL, mais pour le moment ça marche
        $res = $db->prepare('SELECT compte, debit, credit, (SELECT position FROM compta_comptes WHERE id = compte) AS position
            FROM
                (SELECT compte_debit AS compte, SUM(montant) AS debit, NULL AS credit
                    FROM compta_journal WHERE id_exercice = 1 GROUP BY compte_debit
                UNION
                SELECT compte_credit AS compte, NULL AS debit, SUM(montant) AS credit
                    FROM compta_journal WHERE id_exercice = 1 GROUP BY compte_credit)
            WHERE compte IN (SELECT id FROM compta_comptes WHERE position IN ('.implode(', ', $include).'))
            ORDER BY base64(compte) COLLATE BINARY ASC;'
            )->execute();

        while ($row = $res->fetchArray(SQLITE3_NUM))
        {
            list($compte, $debit, $credit, $position) = $row;
            $parent = substr($compte, 0, 2);

            if ($position & Garradin_Compta_Comptes::ACTIF)
            {
                if (!isset($actif['comptes'][$parent]))
                {
                    $actif['comptes'][$parent] = array('comptes' => array(), 'solde' => 0.0);
                }

                $solde = $debit - $credit;

                if (!isset($actif['comptes'][$parent]['comptes'][$compte]))
                {
                    $actif['comptes'][$parent]['comptes'][$compte] = 0.0;
                }

                $actif['comptes'][$parent]['comptes'][$compte] += $solde;
                $actif['total'] += $solde;
                $actif['comptes'][$parent]['solde'] += $solde;
            }

            if ($position & Garradin_Compta_Comptes::PASSIF)
            {
                if (!isset($passif['comptes'][$parent]))
                {
                    $passif['comptes'][$parent] = array('comptes' => array(), 'solde' => 0.0);
                }

                $solde = $credit - $debit;

                if (!isset($passif['comptes'][$parent]['comptes'][$compte]))
                {
                    $passif['comptes'][$parent]['comptes'][$compte] = 0.0;
                }

                $passif['comptes'][$parent]['comptes'][$compte] += $solde;
                $passif['total'] += $solde;
                $passif['comptes'][$parent]['solde'] += $solde;
            }
        }

        $res->finalize();

        return array('actif' => $actif, 'passif' => $passif);
    }
}

?>