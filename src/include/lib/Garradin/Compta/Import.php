<?php

namespace Garradin\Compta;

use \Garradin\DB;
use \Garradin\Utils;
use \Garradin\UserException;

class Import
{
	protected $csv_header = [
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
	];

	public function toCSV($exercice)
	{
		$db = DB::getInstance();

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

		fputcsv($fp, $this->csv_header);

		while ($row = $res->fetchArray(SQLITE3_ASSOC))
		{
			fputcsv($fp, $row);
		}

		fclose($fp);

		return true;
	}

	public function fromCSV($path)
	{
		if (!file_exists($path) || !is_readable($path))
		{
			throw new \RuntimeException('Fichier inconnu : '.$path);
		}

		$fp = fopen($path, 'r');

		if (!$fp)
		{
			return false;
		}

		$db = DB::getInstance();
		$db->begin();
		$comptes = new Comptes;
		$banques = new Comptes_Bancaires;
		$cats = new Categories;
		$journal = new Journal;

		$columns = array_flip($this->csv_header);
		$liste_comptes = $db->getAssoc('SELECT id, id FROM compta_comptes;');
		$liste_cats = $db->getAssoc('SELECT intitule, id FROM compta_categories;');
		$liste_moyens = $cats->listMoyensPaiement();

		$col = function($column) use (&$row, &$columns)
		{
			if (!isset($columns[$column]))
				return null;

			if (!isset($row[$columns[$column]]))
				return null;

			return $row[$columns[$column]];
		};

		$line = 0;
		$delim = Utils::find_csv_delim($fp);
		Utils::skip_bom($fp);

		while (!feof($fp))
		{
			$row = fgetcsv($fp, 4096, $delim);
			$line++;

			if (empty($row))
			{
				continue;
			}

			if ($line === 1)
			{
				if (trim($row[0]) != 'Numéro mouvement')
				{
					throw new UserException('Erreur sur la ligne ' . $line . ' : l\'entête des colonnes est absent ou incorrect.');
				}
				
				continue;
			}
	
			if (count($row) != count($columns))
			{
				$db->rollback();
				throw new UserException('Erreur sur la ligne ' . $line . ' : le nombre de colonnes est incorrect.');
			}

			if (trim($row[0]) !== '' && !is_numeric($row[0]))
			{
				$db->rollback();
				throw new UserException('Erreur sur la ligne ' . $line . ' : la première colonne doit être vide ou contenir le numéro unique d\'opération.');
			}

			$id = $col('Numéro mouvement');
			$date = $col('Date');

			if (!preg_match('!^\d{2}/\d{2}/\d{4}$!', $date))
			{
				$db->rollback();
				throw new UserException('Erreur sur la ligne ' . $line . ' : la date n\'est pas au format jj/mm/aaaa.');
			}

			$date = explode('/', $date);
			$date = $date[2] . '-' . $date[1] . '-' . $date[0];

			// En dehors de l'exercice courant
			if ($db->test('compta_exercices', '(? < debut OR ? > fin) AND cloture = 0', $date, $date))
			{
				continue;
			}

			$debit = $col('Compte de débit - numéro');
			$credit = $col('Compte de crédit - numéro');

			if (trim($debit) == '' && trim($credit) != '')
			{
				$debit = null;
			}
			elseif (trim($debit) != '' && trim($credit) == '')
			{
				$credit = null;
			}

			$cat = $col('Catégorie');
			$moyen = strtoupper(substr($col('Moyen de paiement'), 0, 2));

			if (!$moyen || !array_key_exists($moyen, $liste_moyens))
			{
				$moyen = false;
				$cat = false;
			}

			if ($cat && !array_key_exists($cat, $liste_cats))
			{
				$cat = $moyen = false;
			}

			$data = [
				'libelle'       =>  $col('Libellé'),
				'montant'       =>  (float) $col('Montant'),
				'date'          =>  $date,
				'compte_credit' =>  $credit,
				'compte_debit'  =>  $debit,
				'numero_piece'  =>  $col('Numéro de pièce'),
				'remarques'     =>  $col('Remarques'),
			];

			if ($cat)
			{
				$data['moyen_paiement']	=	$moyen;
				$data['numero_cheque']	=	$col('Numéro de chèque');
				$data['id_categorie']	=	$liste_cats[$cat];
			}

			if (empty($id))
			{
				$journal->add($data);
			}
			else
			{
				$journal->edit($id, $data);
			}
		}

		$db->commit();

		fclose($fp);
		return true;
	}

	public function fromCitizen($path)
	{
		if (!file_exists($path) || !is_readable($path))
		{
			throw new \RuntimeException('Fichier inconnu : '.$path);
		}

		$fp = fopen($path, 'r');

		if (!$fp)
		{
			return false;
		}

		$db = DB::getInstance();
		$db->begin();
		$comptes = new Comptes;
		$banques = new Comptes_Bancaires;
		$cats = new Categories;
		$journal = new Journal;

		$columns = [];
		$liste_comptes = $db->getAssoc('SELECT id, id FROM compta_comptes;');
		$liste_cats = $db->getAssoc('SELECT intitule, id FROM compta_categories;');
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
					$liste_comptes[$compte] = $banques->add([
						'libelle'	=>	$intitule,
						'banque'	=>	'Inconnue',
					]);
				}
				else
				{
					$liste_comptes[$compte] = $comptes->add([
						'id'		=>	$compte,
						'libelle'	=>	$intitule,
						'parent'	=>	substr($compte, 0, -1)
					]);
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

		$line = 0;
		$delim = Utils::find_csv_delim($fp);
		Utils::skip_bom($fp);

		while (!feof($fp))
		{
			$row = fgetcsv($fp, 4096, $delim);
			$line++;

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

			$date = $col('Date');
			$date = \DateTime::createFromFormat('d/m/Y', $date);

			if (!$date)
			{
				$db->rollback();
				throw new UserException(sprintf('Erreur sur la ligne %d : la date "%s" n\'est pas au format jj/mm/aaaa.', $line, $col('Date')));
			}

			$date = $date->format('Y-m-d');

			if ($db->test('compta_exercices', '(? < debut OR ? > fin) AND cloture = 0', $date, $date))
			{
				continue;
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
					$liste_cats[$cat] = $cats->add([
						'intitule'	=>	$cat,
						'type'		=>	$type,
						'compte'	=>	$compte
					]);
				}
			}

			$data = [
				'libelle'       =>  $col('Libellé'),
				'montant'       =>  $col('Montant'),
				'date'          =>  $date,
				'compte_credit' =>  $credit,
				'compte_debit'  =>  $debit,
				'numero_piece'  =>  $col('Numéro de pièce'),
				'remarques'     =>  $col('Remarques'),
			];

			if ($cat)
			{
				$data['moyen_paiement']	=	$moyen;
				$data['numero_cheque']	=	$col('Numéro de chèque');
				$data['id_categorie']	=	$liste_cats[$cat];
			}

			$journal->add($data);
		}

		$db->commit();

		fclose($fp);
		return true;
	}
}
