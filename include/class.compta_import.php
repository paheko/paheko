<?php

class Garradin_Compta_Import
{
	public function toCSV($exercice)
	{
		$db = Garradin_DB::getInstance();

		$header = array(
			'Numéro mouvement',
			'Date',
			'Type de mouvement',
			'Catégorie',
			'Libellé',
			'Montant',
			'Compte de débit - numéro',
			'Compte de débit - libellé',
			'Compte de crédit - numéro',
			'Compte de crédit - libellé',
			'Moyen de paiement',
			'Numéro de chèque',
			'Numéro de pièce',
			'Remarques'
		);

		$res = $db->prepare('SELECT
			journal.id,
			strftime(\'%d/%m/%Y\', date) AS date,
			(CASE cat.type WHEN 1 THEN \'Recette\' WHEN -1 THEN \'Dépense\' ELSE \'Autre\' END) AS type,
			(CASE cat.intitule WHEN NULL THEN \'\' ELSE cat.intitule END) AS cat,
			journal.libelle,
			montant,
			compte_debit,
			debit.libelle AS libelle_debit,
			compte_credit,
			credit.libelle AS libelle_credit,
			(CASE moyen_paiement WHEN NULL THEN \'\' ELSE moyen.nom END) AS moyen,
			numero_cheque,
			numero_piece,
			remarques
			FROM compta_journal AS journal
				LEFT JOIN compta_categories AS cat ON cat.id = journal.id_categorie
				LEFT JOIN compta_comptes AS debit ON debit.id = journal.compte_debit
				LEFT JOIN compta_comptes AS credit ON credit.id = journal.compte_credit
				LEFT JOIN compta_moyens_paiement AS moyen ON moyen.code = journal.moyen_paiement
			WHERE id_exercice = '.(int)$exercice.'
			ORDER BY journal.date;
		')->execute();

		$fp = fopen('php://output', 'w');

		fputcsv($fp, $header);

		while ($row = $res->fetchArray(SQLITE3_ASSOC))
		{
			fputcsv($fp, $row);
		}

		fclose($fp);

		return true;
	}

	public function fromCitizen($path)
	{
		if (!file_exists($path) || !is_readable($path))
		{
			throw new RuntimeException('Fichier inconnu : '.$path);
		}

		$fp = fopen($path, 'r');

		if (!$fp)
		{
			return false;
		}

		require_once GARRADIN_ROOT . '/include/class.compta_comptes.php';
		require_once GARRADIN_ROOT . '/include/class.compta_comptes_bancaires.php';
		require_once GARRADIN_ROOT . '/include/class.compta_categories.php';
		require_once GARRADIN_ROOT . '/include/class.compta_journal.php';

		$db = Garradin_DB::getInstance();
		$db->exec('BEGIN;');
		$comptes = new Garradin_Compta_Comptes;
		$banques = new Garradin_Compta_Comptes_Bancaires;
		$cats = new Garradin_Compta_Categories;
		$journal = new Garradin_Compta_Journal;

		$columns = array();
		$liste_comptes = $db->simpleStatementFetchAssoc('SELECT id, id FROM compta_comptes;');
		$liste_cats = $db->simpleStatementFetchAssoc('SELECT intitule, id FROM compta_categories;');
		$liste_moyens = $cats->listMoyensPaiement();

		$get_compte = function ($compte, $intitule) use (&$liste_comptes, &$comptes, &$banques)
		{
			if (substr($compte, 0, 2) == '51')
			{
				$compte = '512' . substr($compte, -1);
			}

			// Création comptes
			if (!array_key_exists($compte, $liste_comptes))
			{
				if (substr($compte, 0, 3) == '512')
				{
					$liste_comptes[$compte] = $banques->add(array(
						'libelle'	=>	$intitule,
						'banque'	=>	'Inconnue',
					));
				}
				else
				{
					$liste_comptes[$compte] = $comptes->add(array(
						'id'		=>	$compte,
						'libelle'	=>	$intitule,
						'parent'	=>	substr($compte, 0, -1)
					));
				}
			}

			return $compte;
		};

		$col = function($column) use (&$row, &$columns)
		{
			if (!isset($columns[$column]))
				return null;

			if (!isset($row[$columns[$column]]))
				return null;

			return $row[$columns[$column]];
		};

		while (!feof($fp))
		{
			$row = fgetcsv($fp, 4096, ';');

			if (empty($row))
			{
				continue;
			}

			if (empty($columns))
			{
				$columns = $row;
				$columns = array_flip($columns);
				continue;
			}

			$date = explode('/', $col('Date'));
			$date = $date[2] . '-' . $date[1] . '-' . $date[0];

			if ($db->simpleQuerySingle('SELECT 1 FROM compta_exercices
				WHERE (? < debut || ? > fin) AND cloture = 0;', false, $date, $date))
			{
				$db->exec('ROLLBACK;');
				throw new UserException('Impossible d\'importer dans cet exercice : une opération est datée au '.$col('Date').', en dehors de l\'exercice.');
			}

			if ($db->simpleQuerySingle('SELECT 1 FROM compta_exercices WHERE ? < debut AND cloture = 0;', false, $date))
			{
				$db->exec('ROLLBACK;');
				throw new UserException('Une opération est datée du '.$col('Date').', soit avant la date de début de l\'exercice.');
			}

			$debit = $get_compte($col('Compte débité - Numéro'), $col('Compte débité - Intitulé'));
			$credit = $get_compte($col('Compte crédité - Numéro'), $col('Compte crédité - Intitulé'));

			$cat = $col('Rubrique');
			$moyen = strtoupper(substr($col('Moyen de paiement'), 0, 2));

			if (!$moyen || !array_key_exists($moyen, $liste_moyens))
			{
				$moyen = false;
				$cat = false;
			}

			if ($cat && !array_key_exists($cat, $liste_cats))
			{
				if ($col('Nature') == 'Recette')
				{
					$type = $cats::RECETTES;
					$compte = $credit;
				}
				elseif ($col('Nature') == 'Dépense')
				{
					$type = $cats::DEPENSES;
					$compte = $debit;
				}
				else
				{
					$type = $cats::AUTRES;
					$cat = false;
				}

				if ($type != $cats::AUTRES)
				{
					$liste_cats[$cat] = $cats->add(array(
						'intitule'	=>	$cat,
						'type'		=>	$type,
						'compte'	=>	$compte
					));
				}
			}

			$data = array(
				'libelle'       =>  $col('Libellé'),
				'montant'       =>  $col('Montant'),
				'date'          =>  $date,
				'compte_credit' =>  $credit,
				'compte_debit'  =>  $debit,
				'numero_piece'  =>  $col('Numéro de pièce'),
				'remarques'     =>  $col('Remarques'),
			);

			if ($cat)
			{
				$data['moyen_paiement']	=	$moyen;
				$data['numero_cheque']	=	$col('Numéro de chèque');
				$data['id_categorie']	=	$liste_cats[$cat];
			}

			$journal->add($data);
		}

		$db->exec('END;');

		fclose($fp);
		return true;
	}
}

?>