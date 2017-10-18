<?php

namespace Garradin\Compta;

use \Garradin\DB;
use \Garradin\Utils;
use \Garradin\UserException;

class Rapports
{
    protected function getWhereClause(array $criterias)
    {
        $where = [];
        $db = DB::getInstance();

        foreach ($criterias as $name => $value)
        {
            $where[] = $db->where($name, $value);
        }

        return implode(' AND ', $where);
    }

    public function journal(array $criterias)
    {
        $db = DB::getInstance();
        return $db->get('SELECT *, strftime(\'%s\', date) AS date FROM compta_journal
            WHERE ' . $this->getWhereClause($criterias) . ' ORDER BY date, id;');
    }

    public function grandLivre(array $criterias)
    {
        $db = DB::getInstance();
        $livre = ['classes' => [], 'debit' => 0.0, 'credit' => 0.0];
        $where = $this->getWhereClause($criterias);

        $res = $db->preparedQuery('SELECT compte FROM
            (SELECT compte_debit AS compte FROM compta_journal
                    WHERE ' . $where . ' GROUP BY compte_debit
                UNION
                SELECT compte_credit AS compte FROM compta_journal
                    WHERE ' . $where . ' GROUP BY compte_credit)
            ORDER BY compte ASC;');

        while ($row = $res->fetchArray(SQLITE3_NUM))
        {
            $compte = $row[0];

            if (is_null($compte))
                continue;

            $classe = substr($compte, 0, 1);
            $parent = substr($compte, 0, 2);

            if (!array_key_exists($classe, $livre['classes']))
            {
                $livre['classes'][$classe] = [];
            }

            if (!array_key_exists($parent, $livre['classes'][$classe]))
            {
                $livre['classes'][$classe][$parent] = [
                    'total'         =>  0.0,
                    'comptes'       =>  [],
                ];
            }

            $livre['classes'][$classe][$parent]['comptes'][$compte] = ['debit' => 0.0, 'credit' => 0.0, 'journal' => []];

            $livre['classes'][$classe][$parent]['comptes'][$compte]['journal'] = $db->get(
                'SELECT *, strftime(\'%s\', date) AS date FROM (
                    SELECT * FROM compta_journal WHERE compte_debit = :compte AND ' . $where . '
                    UNION
                    SELECT * FROM compta_journal WHERE compte_credit = :compte AND ' . $where . '
                    )
                ORDER BY date, numero_piece, id;', 
                ['compte' => $compte]);

            $solde = 0.0;

            foreach ($livre['classes'][$classe][$parent]['comptes'][$compte]['journal'] as &$ligne)
            {
                if ($ligne->compte_credit == $compte)
                {
                    $solde += $ligne->montant;
                }
                else
                {
                    $solde -= $ligne->montant;
                }

                $ligne->solde = $solde;
            }

            $debit = (float) $db->firstColumn(
                'SELECT SUM(montant) FROM compta_journal WHERE compte_debit = ? AND ' . $where . ';',
                $compte);

            $credit = (float) $db->firstColumn(
                'SELECT SUM(montant) FROM compta_journal WHERE compte_credit = ? AND ' . $where . ';',
                $compte);

            $livre['classes'][$classe][$parent]['comptes'][$compte]['debit'] = $debit;
            $livre['classes'][$classe][$parent]['comptes'][$compte]['credit'] = $credit;
            $livre['classes'][$classe][$parent]['comptes'][$compte]['solde'] = $credit - $debit;

            $livre['classes'][$classe][$parent]['total'] += $debit;
            $livre['classes'][$classe][$parent]['total'] -= $credit;

            $livre['debit'] += $debit;
            $livre['credit'] += $credit;
        }

        $res->finalize();

        return $livre;
    }

    public function compteResultat(array $criterias)
    {
        $db = DB::getInstance();
        $where = $this->getWhereClause($criterias);

        $charges    = ['comptes' => [], 'total' => 0.0];
        $produits   = ['comptes' => [], 'total' => 0.0];
        $resultat   = 0.0;

        $res = $db->preparedQuery('SELECT compte, SUM(debit), SUM(credit)
            FROM
                (SELECT compte_debit AS compte, SUM(montant) AS debit, 0 AS credit
                    FROM compta_journal WHERE ' . $where . ' GROUP BY compte_debit
                UNION
                SELECT compte_credit AS compte, 0 AS debit, SUM(montant) AS credit
                    FROM compta_journal WHERE ' . $where . ' GROUP BY compte_credit)
            WHERE compte LIKE \'6%\' OR compte LIKE \'7%\'
            GROUP BY compte
            ORDER BY compte ASC;');

        while ($row = $res->fetchArray(SQLITE3_NUM))
        {
            list($compte, $debit, $credit) = $row;
            $classe = substr($compte, 0, 1);
            $parent = substr($compte, 0, 2);

            if ($classe == 6)
            {
                if (!isset($charges['comptes'][$parent]))
                {
                    $charges['comptes'][$parent] = ['comptes' => [], 'solde' => 0.0];
                }

                $solde = round($debit - $credit, 2);

                if (empty($solde))
                    continue;

                $charges['comptes'][$parent]['comptes'][$compte] = $solde;
                $charges['total'] += $solde;
                $charges['comptes'][$parent]['solde'] += $solde;
            }
            elseif ($classe == 7)
            {
                if (!isset($produits['comptes'][$parent]))
                {
                    $produits['comptes'][$parent] = ['comptes' => [], 'solde' => 0.0];
                }

                $solde = round($credit - $debit, 2);

                if (empty($solde))
                    continue;

                $produits['comptes'][$parent]['comptes'][$compte] = $solde;
                $produits['total'] += $solde;
                $produits['comptes'][$parent]['solde'] += $solde;
            }
        }

        $res->finalize();

        $resultat = $produits['total'] - $charges['total'];

        return ['charges' => $charges, 'produits' => $produits, 'resultat' => $resultat];
    }

    /**
     * Calculer le bilan comptable 
     * @return array    Un tableau multi-dimensionnel avec deux clés : actif et passif
     */
    public function bilan(array $criterias)
    {
        $db = DB::getInstance();
        $where = $this->getWhereClause($criterias);

        $include = [Comptes::ACTIF, Comptes::PASSIF,
            Comptes::PASSIF | Comptes::ACTIF];

        $actif           = ['comptes' => [], 'total' => 0.0];
        $passif          = ['comptes' => [], 'total' => 0.0];
        $actif_ou_passif = ['comptes' => [], 'total' => 0.0];

        $resultat = $this->compteResultat($criterias);

        if ($resultat['resultat'] >= 0)
        {
            $passif['comptes']['12'] = [
                'comptes'   =>  ['120' => $resultat['resultat']],
                'solde'     =>  $resultat['resultat']
            ];

            $passif['total'] = $resultat['resultat'];
        }
        else
        {
            $passif['comptes']['12'] = [
                'comptes'   =>  ['129' => $resultat['resultat']],
                'solde'     =>  $resultat['resultat']
            ];

            $passif['total'] = $resultat['resultat'];
        }

        // Y'a sûrement moyen d'améliorer tout ça pour que le maximum de travail
        // soit fait au niveau du SQL, mais pour le moment ça marche
        $res = $db->preparedQuery('SELECT compte, debit, credit, (SELECT position FROM compta_comptes WHERE id = compte) AS position
            FROM
                (SELECT compte_debit AS compte, SUM(montant) AS debit, NULL AS credit
                    FROM compta_journal WHERE ' . $where . ' GROUP BY compte_debit
                UNION
                SELECT compte_credit AS compte, NULL AS debit, SUM(montant) AS credit
                    FROM compta_journal WHERE ' . $where . ' GROUP BY compte_credit)
            WHERE compte IN (SELECT id FROM compta_comptes WHERE position IN ('.implode(', ', $include).'))
            ORDER BY compte ASC;');

        while ($row = $res->fetchArray(SQLITE3_NUM))
        {
            list($compte, $debit, $credit, $position) = $row;
            $parent = substr($compte, 0, 2);
            $classe = $compte[0];

            if (($position & Comptes::ACTIF) && ($position & Comptes::PASSIF))
            {
                $position = 'actif_ou_passif';
                $solde = $debit - $credit;
            }
            else if ($position & Comptes::ACTIF)
            {
                $position = 'actif';
                $solde = $debit - $credit;
            }
            else if ($position & Comptes::PASSIF)
            {
                $position = 'passif';
                $solde = $credit - $debit;
            }
            else
            {
                continue;
            }

            if (!isset(${$position}['comptes'][$parent]))
            {
                ${$position}['comptes'][$parent] = ['comptes' => [], 'solde' => 0];
            }

            if (!isset(${$position}['comptes'][$parent]['comptes'][$compte]))
            {
                ${$position}['comptes'][$parent]['comptes'][$compte] = 0;
            }

            $solde = round($solde, 2);
            ${$position}['comptes'][$parent]['comptes'][$compte] += $solde;
            ${$position}['total'] += $solde;
            ${$position}['comptes'][$parent]['solde'] += $solde;
        }

        $res->finalize();

        foreach ($actif_ou_passif['comptes'] as $parent=>$p)
        {
            foreach ($p['comptes'] as $compte=>$solde)
            {
                if ($solde > 0)
                {
                    $position = 'actif';
                }
                else if ($solde < 0)
                {
                    $position = 'passif';
                    $solde = -$solde;
                }
                else
                {
                    continue;
                }

                if (!isset(${$position}['comptes'][$parent]))
                {
                    ${$position}['comptes'][$parent] = ['comptes' => [], 'solde' => 0];
                }

                if (!isset(${$position}['comptes'][$parent]['comptes'][$compte]))
                {
                    ${$position}['comptes'][$parent]['comptes'][$compte] = 0;
                }

                ${$position}['comptes'][$parent]['comptes'][$compte] += $solde;
                ${$position}['total'] += $solde;
                ${$position}['comptes'][$parent]['solde'] += $solde;
            }
        }

        // Suppression des soldes nuls
        foreach ($passif['comptes'] as $parent=>$p)
        {
            if ($p['solde'] == 0)
            {
                unset($passif['comptes'][$parent]);
                continue;
            }

            foreach ($p['comptes'] as $id=>$solde)
            {
                if ($solde == 0)
                {
                    unset($passif['comptes'][$parent]['comptes'][$id]);
                }
            }
        }

        foreach ($actif['comptes'] as $parent=>$p)
        {
            if (empty($p['solde']))
            {
                unset($actif['comptes'][$parent]);
                continue;
            }

            foreach ($p['comptes'] as $id=>$solde)
            {
                if (empty($solde))
                {
                    unset($actif['comptes'][$parent]['comptes'][$id]);
                }
            }
        }

        return ['actif' => $actif, 'passif' => $passif];
    }
}
