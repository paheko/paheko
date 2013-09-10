<?php

namespace Garradin;

class Compta_Stats
{
	protected function _byType($type)
	{
		return $this->getStats('SELECT strftime(\'%Y%m\', date) AS date,
			SUM(montant) FROM compta_journal
			WHERE id_categorie IN (SELECT id FROM compta_categories WHERE type = '.$type.')
			AND id_exercice = (SELECT id FROM compta_exercices WHERE cloture = 0)
			GROUP BY strftime(\'%Y-%m\', date) ORDER BY date;');
	}

	public function recettes()
	{
		return $this->_byType(Compta_Categories::RECETTES);
	}

	public function depenses()
	{
		return $this->_byType(Compta_Categories::DEPENSES);
	}

	public function soldeCompte($compte, $augmente = 'debit', $diminue = 'credit')
	{
		$db = DB::getInstance();

		if (strpos($compte, '%') !== false)
		{
			$compte = 'LIKE \''. $db->escapeString($compte) . '\'';
		}
		else
		{
			$compte = '= \''. $db->escapeString($compte) . '\'';
		}

		$stats = $this->getStats('SELECT strftime(\'%Y%m\', date) AS date,
			(COALESCE((SELECT SUM(montant) FROM compta_journal
				WHERE compte_'.$augmente.' '.$compte.' AND id_exercice = cj.id_exercice
				AND date >= strftime(\'%Y-%m-01\', cj.date)
				AND date <= strftime(\'%Y-%m-31\', cj.date)), 0)
			- COALESCE((SELECT SUM(montant) FROM compta_journal
				WHERE compte_'.$diminue.' '.$compte.' AND id_exercice = cj.id_exercice
				AND date >= strftime(\'%Y-%m-01\', cj.date)
				AND date <= strftime(\'%Y-%m-31\', cj.date)), 0)
			) AS solde
			FROM compta_journal AS cj
			WHERE (compte_debit '.$compte.' OR compte_credit '.$compte.')
			AND id_exercice = (SELECT id FROM compta_exercices WHERE cloture = 0)
			GROUP BY strftime(\'%Y-%m\', date) ORDER BY date;');

		$c = 0;
		foreach ($stats as $k=>$v)
		{
			$c += $v;
			$stats[$k] = $c;
		}

		return $stats;
	}

	public function getStats($query)
	{
		$db = DB::getInstance();

		$data = $db->simpleStatementFetchAssoc($query);

		$e = $db->querySingle('SELECT *, strftime(\'%s\', debut) AS debut,
			strftime(\'%s\', fin) AS fin FROM compta_exercices WHERE cloture = 0;', true);

		$y = date('Y', $e['debut']);
		$m = date('m', $e['debut']);
		$max = date('Ym', $e['fin']);

		while ($y . $m <= $max)
		{
			if (!isset($data[$y . $m]))
			{
				$data[$y . $m] = 0;
			}

			if ($m == 12)
			{
				$m = '01';
				$y++;
			}
			else
			{
				$m++;
				$m = str_pad((int)$m, 2, '0', STR_PAD_LEFT);
			}
		}

		ksort($data);

		return $data;
	}
}

?>