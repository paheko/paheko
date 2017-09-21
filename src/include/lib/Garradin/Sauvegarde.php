<?php

namespace Garradin;

class Sauvegarde
{
	const NEED_UPGRADE = 0x01 << 2;
	const NOT_AN_ADMIN = 0x01 << 3;

	const INTEGRITY_FAIL = 41;
	const NOT_A_DB = 42;
	const NO_APP_ID = 43;

	/**
	 * Renvoie la liste des fichiers SQLite sauvegardés
	 * @param  boolean $auto Si true ne renvoie que la liste des sauvegardes automatiques
	 * @return array 		 Liste des fichiers
	 */	
	public function getList($auto = false)
	{
		$ext = $auto ? 'auto\.\d+\.sqlite' : 'sqlite';

		$out = [];
		$dir = dir(DATA_ROOT);

		while ($file = $dir->read())
		{
			if ($file[0] != '.' && is_file(DATA_ROOT . '/' . $file) 
				&& preg_match('![\w\d._-]+\.' . $ext . '$!i', $file) && $file != basename(DB_FILE))
			{
				$out[$file] = filemtime(DATA_ROOT . '/' . $file);
			}
		}

		$dir->close();

		ksort($out);

		return $out;
	}

	/**
	 * Crée une nouvelle sauvegarde
	 * @param  boolean $auto Si true le nom de fichier sera celui de la sauvegarde automatique courante,
	 * sinon le nom sera basé sur la date (sauvegarde manuelle)
	 * @return string Le nom de fichier de la sauvegarde ainsi créée
	 */
	public function create($auto = false)
	{
		$suffix = is_string($auto) ? $auto : ($auto ? 'auto.1' : date('Y-m-d-His'));

		$backup = str_replace('.sqlite', sprintf('.%s.sqlite', $suffix), DB_FILE);
		
		// Acquire a shared lock before copying
		$db = DB::getInstance();
		$db->exec('BEGIN IMMEDIATE TRANSACTION;');
		
		copy(DB_FILE, $backup);
		
		$db->exec('END TRANSACTION;');
		return basename($backup);
	}

	/**
	 * Effectue une rotation des sauvegardes automatiques
	 * association.auto.1.sqlite deviendra association.auto.2.sqlite par exemple
	 * @return boolean true
	 */
	public function rotate()
	{
		$config = Config::getInstance();
		$nb = $config->get('nombre_sauvegardes');

		$list = $this->getList(true);
		krsort($list);

		if (count($list) >= $nb)
		{
			$this->remove(key($list));
			array_shift($list);
		}

		foreach ($list as $f=>$d)
		{
			$new = preg_replace_callback('!\.auto\.(\d+)\.sqlite$!', function ($m) {
				return '.auto.' . ((int) $m[1] + 1) . '.sqlite';
			}, $f);

			rename(DATA_ROOT . '/' . $f, DATA_ROOT . '/' . $new);
		}

		return true;
	}

	/**
	 * Crée une sauvegarde automatique si besoin est
	 * @return boolean true
	 */
	public function auto()
	{
		$config = Config::getInstance();

		// Pas besoin d'aller plus loin si on ne fait pas de sauvegarde auto
		if ($config->get('frequence_sauvegardes') == 0 || $config->get('nombre_sauvegardes') == 0)
			return true;

		$list = $this->getList(true);

		if (count($list) > 0)
		{
			$last = current($list);
		}
		else
		{
			$last = false;
		}

		// Test de la date de création de la dernière sauvegarde
		if ($last >= (time() - ($config->get('frequence_sauvegardes') * 3600 * 24)))
		{
			return true;
		}

		// Si pas de modif depuis la dernière sauvegarde, ça sert à rien d'en faire
		if ($last >= filemtime(DB_FILE))
		{
			return true;
		}

		$this->rotate();
		$this->create(true);

		return true;
	}

	/**
	 * Efface une sauvegarde locale
	 * @param  string $file Nom du fichier à supprimer
	 * @return boolean		true si le fichier a bien été supprimé, false sinon
	 */
	public function remove($file)
	{
		if (preg_match('!\.\.+!', $file) || !preg_match('!^[\w\d._-]+\.sqlite$!i', $file) 
			|| $file == basename(DB_FILE))
		{
			throw new UserException('Nom de fichier non valide.');
		}

		return unlink(DATA_ROOT . '/' . $file);
	}

	/**
	 * Renvoie sur la sortie courante le contenu du fichier de base de données courant
	 * @return boolean true
	 */
	public function dump()
	{
		// Acquire a shared lock before copying
		$db = DB::getInstance();
		$db->exec('BEGIN IMMEDIATE TRANSACTION;');

		$in = fopen(DB_FILE, 'r');
        $out = fopen('php://output', 'w');

        while (!feof($in))
        {
        	fwrite($out, fread($in, 8192));
        }

        fclose($in);

        $db->exec('END TRANSACTION;');

        // Ajout du hash pour vérification intégrité
        fwrite($out, sha1_file(DB_FILE));

        fclose($out);
        return true;
	}

	/**
	 * Restaure une sauvegarde locale
	 * @param  string $file Le nom de fichier à utiliser comme point de restauration
	 * @return boolean true si la restauration a fonctionné, false sinon
	 */
	public function restoreFromLocal($file)
	{
		if (preg_match('!\.\.+!', $file) || !preg_match('!^[\w\d._ -]+$!iu', $file))
		{
			throw new UserException('Nom de fichier non valide.');
		}

		if (!file_exists(DATA_ROOT . '/' . $file))
		{
			throw new UserException('Le fichier fourni n\'existe pas.');
		}

		return $this->restoreDB(DATA_ROOT . '/' . $file);
	}

	/**
	 * Restaure une copie distante (fichier envoyé)
	 * @param  array   $file    Tableau provenant de $_FILES
	 * @param  integer $user_id ID du membre actuellement connecté, utilisé pour 
	 * vérifier qu'il est toujours administrateur dans la sauvegarde
	 * @param  boolean $check_integrity Vérifier l'intégrité de la sauvegarde avant de restaurer
	 * @return boolean true
	 */
	public function restoreFromUpload($file, $user_id, $check_integrity = true)
	{
		if (empty($file['size']) || empty($file['tmp_name']) || !empty($file['error']))
		{
			throw new UserException('Le fichier n\'a pas été correctement envoyé. Essayer de le renvoyer à nouveau.');
		}

		if ($check_integrity)
		{
			$integrity = $this->checkIntegrity($file['tmp_name']);

			if ($integrity === null)
			{
				throw new UserException('Le fichier fourni n\'est pas une base de donnée SQLite3.', self::NOT_A_DB);
			}
			elseif ($integrity === false)
			{
				throw new UserException('Le fichier fourni a été modifié par un programme externe.', self::INTEGRITY_FAIL);
			}
		}

		$r = $this->restoreDB($file['tmp_name'], $user_id);

		if ($r)
		{
			unlink($file['tmp_name']);
		}

		return $r;
	}

	/**
	 * Vérifie l'intégrité d'une sauvegarde Garradin
	 * @param  string $file Chemin absolu vers la base de donnée
	 * @return boolean
	 */
	protected function checkIntegrity($file_path, $remove_hash = true)
	{
		$size = filesize($file_path);
		$fp = fopen($file_path, 'r+');

		$header = fread($fp, 16);

		// Vérifie que le fichier est bien une base SQLite3
		if ($header !== "SQLite format 3\000")
		{
			fclose($fp);
			return null;
		}

		fseek($fp, -40, SEEK_END);

		$hash = fread($fp, 40);

		// Ne ressemble pas à un hash sha1
		if (!preg_match('/[a-f0-9]{40}/', $hash))
		{
			fclose($fp);
			return false;
		}

		$max = $size - 40;

		// Suppression du hash
		if ($remove_hash)
		{
			ftruncate($fp, $max);
		}

		fclose($fp);

		$file_hash = sha1_file($file_path);

		// Vérification du hash
		return ($file_hash === $hash);
	}

	/**
	 * Restauration de base de données, la fonction qui le fait vraiment
	 * @param  string $file Chemin absolu vers la base de données à utiliser
	 * @return mixed 		true si rien ne va plus, ou self::NEED_UPGRADE si la version de la DB
	 * ne correspond pas à la version de Garradin (mise à jour nécessaire).
	 */
	protected function restoreDB($file, $user_id = false)
	{
		$return = 1;

		// Essayons déjà d'ouvrir la base de données à restaurer en lecture
		try {
			$db = new \SQLite3($file, SQLITE3_OPEN_READONLY);
		}
		catch (\Exception $e)
		{
			throw new UserException('Le fichier fourni n\'est pas une base de données valide. ' .
				'Message d\'erreur de SQLite : ' . $e->getMessage(), self::NOT_A_DB);
		}

		try {
			// Regardons ensuite si la base de données n'est pas corrompue
			$check = $db->querySingle('PRAGMA integrity_check;', false);
		}
		catch (\Exception $e)
		{
			// Ici SQLite peut rejeter un message type "file is encrypted or is not a db"
			throw new UserException('Le fichier fourni n\'est pas une base de données valide. ' .
				'Message d\'erreur de SQLite : ' . $e->getMessage(), self::NOT_A_DB);
		}

		if (strtolower(trim($check)) != 'ok')
		{
			throw new UserException('Le fichier fourni est corrompu. SQLite a trouvé ' . $check . ' erreurs.');
		}

		// On ne peut pas faire de vérifications très poussées sur la structure de la base de données,
		// celle-ci pouvant changer d'une version à l'autre et on peut vouloir importer une base
		// un peu vieille, mais on vérifie quand même que ça ressemble un minimum à une base garradin
		$table = $db->querySingle('SELECT 1 FROM sqlite_master WHERE type=\'table\' AND tbl_name=\'config\';');

		if (!$table)
		{
			throw new UserException('Le fichier fourni ne semble pas contenir de données liées à Garradin.');
		}

		// On récupère la version
		$version = $db->querySingle('SELECT valeur FROM config WHERE cle=\'version\';');

		// Vérification de l'AppID pour les versions récentes
		if (version_compare($version, '0.8.0', '>='))
		{
			$appid = $db->querySingle('PRAGMA application_id;', false);

			if ($appid !== DB::APPID)
			{
				throw new UserException('Ce fichier n\'est pas une sauvegarde Garradin (application_id ne correspond pas).', self::NO_APP_ID);
			}
		}

		if ($user_id)
		{
			// Empêchons l'admin de se tirer une balle dans le pied
			$is_still_admin = $db->querySingle('SELECT 1 FROM membres_categories
				WHERE id = (SELECT id_categorie FROM membres WHERE id = ' . (int) $user_id . ')
				AND droit_config >= ' . Membres::DROIT_ADMIN . '
				AND droit_connexion >= ' . Membres::DROIT_ACCES);

			if (!$is_still_admin)
			{
				$return |= self::NOT_AN_ADMIN;
			}
		}


		$db->close();

		$backup = str_replace('.sqlite', date('.Y-m-d-His') . '.avant_restauration.sqlite', DB_FILE);

		DB::getInstance()->close();
		
		if (!rename(DB_FILE, $backup))
		{
			throw new \RuntimeException('Unable to backup current DB file.');
		}

		if (!copy($file, DB_FILE))
		{
			rename($backup, DB_FILE);
			throw new \RuntimeException('Unable to copy backup DB to main location.');
		}

		if ($return & self::NOT_AN_ADMIN)
		{
			// Forcer toutes les catégories à pouvoir gérer les droits
			$db = DB::getInstance();
			$db->update('membres_categories', [
				'droit_membres' => Membres::DROIT_ADMIN,
				'droit_connexion' => Membres::DROIT_ACCES
			]);
		}

		if ($version != garradin_version())
		{
			$return |= self::NEED_UPGRADE;
		}

		return $return;
	}

	/**
	 * Taille de la base de données actuelle
	 * @return integer Taille en octets du fichier SQLite
	 */
	public function getDBSize()
	{
		return filesize(DB_FILE);
	}

	/**
	 * Taille occupée par les fichiers dans la base de données
	 * @return integer Taille en octets
	 */
	public function getDBFilesSize()
	{
		$db = DB::getInstance();
		return (int) $db->firstColumn('SELECT SUM(taille) FROM fichiers_contenu;');
	}
}