<?php

namespace Garradin\Compta;

use \Garradin\DB;
use \Garradin\Utils;
use \Garradin\UserException;

class Stats
{
	protected function _parRepartitionCategorie($type)
	{
		return DB::getInstance()->get('SELECT SUM(montant) AS somme, id_categorie
			FROM compta_journal
			WHERE id_categorie IN (SELECT id FROM compta_categories WHERE type = ?)
			AND id_exercice = (SELECT id FROM compta_exercices WHERE cloture = 0)
			GROUP BY id_categorie ORDER BY somme DESC;', $type);
	}

	public function repartitionRecettes()
	{
		return $this->_parRepartitionCategorie(Categories::RECETTES);
	}

	public function repartitionDepenses()
	{
		return $this->_parRepartitionCategorie(Categories::DEPENSES);
	}

	protected function _parType($type)
	{
		return $this->getStats('SELECT strftime(\'%Y%m\', date) AS date,
			SUM(montant) FROM compta_journal
			WHERE id_categorie IN (SELECT id FROM compta_categories WHERE type = :type)
			AND id_exercice = (SELECT id FROM compta_exercices WHERE cloture = 0)
			GROUP BY strftime(\'%Y-%m\', date) ORDER BY date;',
			['type' => $type]);
	}

	public function recettes()
	{
		return $this->_parType(Categories::RECETTES);
	}

	public function depenses()
	{
		return $this->_parType(Categories::DEPENSES);
	}

	public function soldeCompte(int $compte, ?int $id_exercice = null)
	{
		$db = DB::getInstance();

		$stats = $this->getAssoc('SELECT strftime(\'%Y%m\', m.date), SUM(credit) - SUM(debit)
			FROM compta_mouvements_lignes AS l
            INNER JOIN compta_mouvements AS m ON m.id = l.id_mouvement
            WHERE compte = ? AND id_exercice = ?
            GROUP BY strftime(\'%Y%m\', m.date) ORDER BY m.date;');

		$c = 0;
		foreach ($stats as $k=>$v)
		{
			$c += $v;
			$stats[$k] = $c;
		}

		return $stats;
	}

	public function getStats($query, Array $args = [])
	{
		$db = DB::getInstance();

		$data = $db->getAssoc($query, $args);

		$e = $db->first('SELECT *, strftime(\'%s\', debut) AS debut,
			strftime(\'%s\', fin) AS fin FROM compta_exercices
			WHERE cloture = 0 LIMIT 1;');

		if (!$e)
		{
			return [];
		}

		$y = date('Y', $e->debut);
		$m = date('m', $e->debut);
		$max = date('Ym', $e->fin);

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
