<?php

require_once __DIR__ . '/class.compta_comptes.php';

class Garradin_Compta_Stats
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
		return $this->_byType(-1);
	}

	public function depenses()
	{
		return $this->_byType(1);
	}

	public function getStats($query)
	{
		$db = Garradin_DB::getInstance();

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