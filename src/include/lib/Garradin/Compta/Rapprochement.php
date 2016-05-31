<?php

namespace Garradin\Compta;

use \Garradin\DB;
use \Garradin\Utils;
use \Garradin\UserException;
use \Garradin\Compta\Journal;
use \Garradin\Compta\Comptes_Bancaires;

class Rapprochement
{
    public function getJournal($compte, $debut, $fin, &$solde_initial, &$solde_final)
    {
        $db = DB::getInstance();

        $exercice = $db->querySingle('SELECT id FROM compta_exercices WHERE cloture = 0 LIMIT 1;');

        $query = 'SELECT 
            COALESCE((SELECT SUM(montant) FROM compta_journal WHERE compte_debit = :compte AND id_exercice = :exercice AND date < :date), 0)
            - COALESCE((SELECT SUM(montant) FROM compta_journal WHERE compte_credit = :compte AND id_exercice = :exercice AND date < :date), 0)';

        $solde_initial = $solde = $db->simpleQuerySingle($query, false, [
            'compte'    =>  $compte,
            'date'      =>  $debut,
            'exercice'  =>  $exercice
        ]);

        $query = '
            SELECT j.*, strftime(\'%s\', j.date) AS date,
                (CASE WHEN j.compte_debit = :compte THEN j.montant ELSE -(j.montant) END) AS solde,
                r.date AS date_rapprochement
            FROM compta_journal AS j
                LEFT JOIN compta_rapprochement AS r ON r.id_operation = j.id
            WHERE (compte_debit = :compte OR compte_credit = :compte) AND id_exercice = :exercice
                AND j.date >= :debut AND j.date <= :fin
            ORDER BY date ASC;';

        $result = $db->simpleStatementFetch($query, DB::ASSOC, [
            'compte'    =>  $compte,
            'debut'     =>  $debut,
            'fin'       =>  $fin,
            'exercice'  =>  $exercice
        ]);

        foreach ($result as &$row)
        {
            $solde += $row['solde'];
            $row['solde'] = $solde;
        }

        $solde_final = $solde;

        return $result;
    }

    public function record($compte, $journal, $cases, $auteur)
    {
        if (!is_array($journal))
        {
            throw new \UnexpectedValueException('$journal doit être un tableau.');
        }

        if (!is_array($cases) && empty($cases))
        {
            $cases = [];
        }

        $db = DB::getInstance();
        $db->exec('BEGIN;');

        // Synchro des trucs cochés
        $st = $db->prepare('INSERT OR REPLACE INTO compta_rapprochement (id_operation, id_auteur) 
            VALUES (:operation, :auteur);');
        $st->bindValue(':auteur', (int)$auteur, \SQLITE3_INTEGER);

        foreach ($journal as $row)
        {
            if (!array_key_exists($row['id'], $cases))
                continue;

            $st->bindValue(':operation', (int)$row['id'], \SQLITE3_INTEGER);
            $st->execute();
        }

        // Synchro des trucs NON cochés
        $st = $db->prepare('DELETE FROM compta_rapprochement WHERE id_operation = :id;');

        foreach ($journal as $row)
        {
            if (array_key_exists($row['id'], $cases))
                continue;

            $st->bindValue(':id', (int)$row['id'], \SQLITE3_INTEGER);
            $st->execute();
        }

        $db->exec('END;');
        return true;
    }
}