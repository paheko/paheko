<?php

require_once __DIR__ . '/class.compta_comptes.php';

class Garradin_Compta_Stats
{
	public function recettes()
	{
		return $this->getStats('SELECT date, SUM(montant) FROM compta_journal
			WHERE id_categorie IN (SELECT id FROM compta_categories WHERE type = -1)
			AND date > ?
			GROUP BY date ORDER BY date;');
	}

	public function depenses()
	{
		return $this->getStats('SELECT date, SUM(montant) FROM compta_journal
			WHERE id_categorie IN (SELECT id FROM compta_categories WHERE type = 1)
			AND date > ?
			GROUP BY date ORDER BY date;');
	}

	public function actif()
	{
		return $this->getStats('SELECT date, (SELECT SUM(sub.montant) FROM compta_journal AS sub WHERE sub.date <= date)
			FROM compta_journal WHERE compte_credit IN (SELECT id FROM compta_comptes
				WHERE position = '.(int)Garradin_Compta_Comptes::ACTIF.')
			AND date > ?
			GROUP BY date ORDER BY date;');
	}

	public function passif()
	{
		return $this->getStats('SELECT date, (SELECT SUM(sub.compte_debit) FROM compta_journal AS sub WHERE sub.date <= date)
			FROM compta_journal WHERE compte_debit IN (SELECT id FROM compta_comptes
				WHERE position = '.(int)Garradin_Compta_Comptes::PASSIF.')
			AND date > ?
			GROUP BY date ORDER BY date;');
	}

	public function getStats($query)
	{
		$db = Garradin_DB::getInstance();

		$start = strtotime('1 month ago');

		$data = $db->simpleStatementFetchAssoc($query, date('Y-m-d', $start));

		$now = $start;
		$today = time();

		while ($now < $today)
		{
			$day = date('Y-m-d', $now);

			if (!array_key_exists($day, $data))
			{
				$data[$day] = 0;
			}

			$now = strtotime('+1 day', $now);
		}

		return $data;
	}
}

?>