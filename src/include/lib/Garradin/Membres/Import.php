<?php

namespace Garradin\Membres;

use Garradin\Membres;
use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Import
{
	/**
	 * Champs du CSV de Galette
	 * les lignes vides ('') ne seront pas proposées à l'import
	 * @var array
	 */
	public $galette_fields = [
		'Numéro',
		1,
		'Nom',
		'Prénom',
		'Pseudo',
		'Société',
		2,
		'Date de naissance',
		3,
		'Adresse, ligne 1',
		'Adresse, ligne 2',
		'Code postal',
		'Ville',
		'Pays',
		'Téléphone fixe',
		'Téléphone mobile',
		'E-Mail',
		'Site web',
		'ICQ',
		'MSN',
		'Jabber',
		'Infos (réservé administrateur)',
		'Infos (public)',
		'Profession',
		'Identifiant',
		'Mot de passe',
		'Date création fiche',
		'Date modification fiche',
		4, // activite_adh
		5, // bool_admin_adh
		6, // bool_exempt_adh
		7, // bool_display_info
		8, // date_echeance
		9, // pref_lang
		'Lieu de naissance',
		10, // GPG id
		11 // Fingerprint
	];

	/**
	 * Importer un CSV de la liste des membres depuis Galette
	 * @param  string $path              Chemin vers le CSV
	 * @param  array  $translation_table Tableau indiquant la correspondance à effectuer entre les champs
	 * de Galette et ceux de Garradin. Par exemple : ['Date création fiche' => 'date_inscription']
	 * @return boolean                   TRUE en cas de succès
	 */
	public function fromGalette($path, $translation_table)
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
		$db->exec('BEGIN;');
		$membres = new Membres;

		$columns = array_flip($this->galette_fields);

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

		while (!feof($fp))
		{
			$row = fgetcsv($fp, 4096, $delim);
			$line++;

			if (empty($row))
			{
				continue;
			}

			if (count($row) != count($columns))
			{
				$db->exec('ROLLBACK;');
				throw new UserException('Erreur sur la ligne ' . $line . ' : le nombre de colonnes est incorrect.');
			}

			$data = [];

			foreach ($translation_table as $galette=>$garradin)
			{
				// Champs qu'on ne veut pas importer
				if (empty($garradin))
					continue;

				// Concaténer plusieurs champs
				if (isset($data[$garradin]))
					$data[$garradin] .= "\n" . $col($galette);
				else
					$data[$garradin] = $col($galette);
			}

			try {
				$membres->add($data);
			}
			catch (UserException $e)
			{
				$db->exec('ROLLBACK;');
				throw new UserException('Erreur sur la ligne ' . $line . ' : ' . $e->getMessage());
			}
		}

		$db->exec('END;');

		fclose($fp);
		return true;
	}

	/**
	 * Importer un CSV de la liste des membres depuis un export Garradin
	 * @param  string $path 	Chemin vers le CSV
	 * @return boolean          TRUE en cas de succès
	 */
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
		$db->exec('BEGIN;');
		$membres = new Membres;

		// On récupère les champs qu'on peut importer
		$champs = Config::getInstance()->get('champs_membres')->getAll();
		$champs = array_keys($champs);
		$champs[] = 'date_inscription';
		//$champs[] = 'date_connexion';
		$champs[] = 'id';
		//$champs[] = 'id_categorie';

		$line = 0;
		$delim = Utils::find_csv_delim($fp);

		while (!feof($fp))
		{
			$row = fgetcsv($fp, 4096, $delim);

			$line++;

			if (empty($row))
			{
				continue;
			}

			if ($line == 1)
			{
				if (is_numeric($row[0]))
				{
					throw new UserException('Erreur sur la ligne 1 : devrait contenir l\'en-tête des colonnes.');
				}

				$columns = array_flip($row);
				continue;
			}

			if (count($row) != count($columns))
			{
				$db->exec('ROLLBACK;');
				throw new UserException('Erreur sur la ligne ' . $line . ' : le nombre de colonnes est incorrect.');
			}

			$data = [];

			foreach ($columns as $name=>$id)
			{
				$name = trim($name);
				
				// Champs qui n'existent pas dans le schéma actuel
				if (!in_array($name, $champs))
					continue;

				if (trim($row[$id]) !== '')
					$data[$name] = $row[$id];
			}

			if (!empty($data['id']) && $data['id'] > 0)
			{
				$id = (int)$data['id'];
			}
			else
			{
				$id = false;
			}

			unset($data['id']);

			try {
				if ($id)
					$membres->edit($id, $data);
				else
					$membres->add($data);
			}
			catch (UserException $e)
			{
				$db->exec('ROLLBACK;');
				throw new UserException('Erreur sur la ligne ' . $line . ' : ' . $e->getMessage());
			}
		}

		$db->exec('END;');

		fclose($fp);
		return true;
	}

    public function toCSV()
    {
        $db = DB::getInstance();

        $res = $db->prepare('SELECT m.id, c.nom AS categorie, m.* FROM membres AS m 
            LEFT JOIN membres_categories AS c ON m.id_categorie = c.id ORDER BY c.id;')->execute();

        $fp = fopen('php://output', 'w');
        $header = false;

        while ($row = $res->fetchArray(SQLITE3_ASSOC))
        {
            unset($row['passe']);

            if (!$header)
            {
                fputcsv($fp, array_keys($row));
                $header = true;
            }

            fputcsv($fp, $row);
        }

        fclose($fp);

        return true;
    }
}