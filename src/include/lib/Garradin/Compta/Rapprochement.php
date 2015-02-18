<?php

namespace Garradin\Compta;

use \Garradin\DB;
use \Garradin\Utils;
use \Garradin\UserException;
use \Garradin\Compta\Journal;
use \Garradin\Compta\Comptes_Bancaires;

class Rapprochement
{
    public function getJournal($compte, $debut, $fin)
    {
        $db = DB::getInstance();

        $exercice = $db->querySingle('SELECT id FROM compta_exercices WHERE cloture = 0 LIMIT 1;');

        $query = '
        	SELECT j.*, strftime(\'%s\', j.date) AS date, 
            	(CASE WHEN j.compte_debit = :compte THEN j.montant ELSE -(j.montant) END) AS solde,
            	r.date
            FROM compta_journal AS j
            	LEFT JOIN compta_rapprochement AS r ON r.operation = j.id
            WHERE (compte_debit = :compte OR compte_credit = :compte) AND id_exercice = :exercice
            	AND j.date >= :debut AND j.date <= :fin
            ORDER BY date ASC;';

        $result = $db->simpleStatementFetch($query, DB::ASSOC, [
        	'compte'	=>	$compte,
        	'debut'		=>	$debut,
        	'fin'		=>	$fin,
        	'exercice'	=>	$exercice
        ]);

        $solde = 0.0;

        foreach ($result as &$row)
        {
            $solde += $row['solde'];
            $row['solde'] = $solde;
        }

        return $result;
    }

    public function record($compte, $operations, $auteur)
    {
    	if (!is_array($operations))
    	{
    		throw new \UnexpectedValueException('$operations doit être un tableau.');
    	}

    	$db = DB::getInstance();
    	$db->exec('BEGIN;');
    	$st = $db->prepare('INSERT OR REPLACE INTO compta_rapprochement (operation, auteur) 
    		VALUES (:operation, :auteur);');
    	$st->bindValue(':auteur', (int)$auteur, \SQLITE3_INTEGER);

    	foreach ($operations as $row)
    	{
    		$st->bindValue(':operation', (int)$row, \SQLITE3_INTEGER);
    		$st->execute();
    	}

    	$db->exec('END;');
    	return true;
    }
}