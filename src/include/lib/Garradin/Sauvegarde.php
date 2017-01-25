<?php

namespace Garradin;

class Sauvegarde
{
	const NEED_UPGRADE = 'nu';

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
		$backup = str_replace('.sqlite', ($auto ? '.auto.1' : date('.Y-m-d-His')) . '.sqlite', DB_FILE);
		copy(DB_FILE, $backup);
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
		$in = fopen(DB_FILE, 'r');
        $out = fopen('php://output', 'w');

        while (!feof($in))
        {
        	fwrite($out, fread($in, 8192));
        }

        fclose($in);
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
	 * @param  array  $file Tableau provenant de $_FILES
	 * @return boolean true
	 */
	public function restoreFromUpload($file, $user_id)
	{
		if (empty($file['size']) || empty($file['tmp_name']) || !empty($file['error']))
		{
			throw new UserException('Le fichier n\'a pas été correctement envoyé. Essayer de le renvoyer à nouveau.');
		}

		$r = $this->restoreDB($file['tmp_name'], $user_id);

		if ($r)
		{
			unlink($file['tmp_name']);
		}

		return $r;
	}

	/**
	 * Restauration de base de données, la fonction qui le fait vraiment
	 * @param  string $file Chemin absolu vers la base de données à utiliser
	 * @return mixed 		true si rien ne va plus, ou self::NEED_UPGRADE si la version de la DB
	 * ne correspond pas à la version de Garradin (mise à jour nécessaire).
	 */
	protected function restoreDB($file, $user_id = false)
	{
		// Essayons déjà d'ouvrir la base de données à restaurer en lecture
		try {
			$db = new \SQLite3($file, SQLITE3_OPEN_READONLY);
		}
		catch (\Exception $e)
		{
			throw new UserException('Le fichier fourni n\'est pas une base de données valide. ' .
				'Message d\'erreur de SQLite : ' . $e->getMessage());
		}

		// Regardons ensuite si la base de données n'est pas corrompue
		$check = $db->querySingle('PRAGMA integrity_check;');

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

		if ($user_id)
		{
			// Empêchons l'admin de se tirer une balle dans le pied
			$is_still_admin = $db->querySingle('SELECT 1 FROM membres_categories 
				WHERE id = (SELECT id_categorie FROM membres WHERE id = ' . (int) $user_id . ')
				AND droit_config >= ' . Membres::DROIT_ADMIN . '
				AND droit_connexion >= ' . Membres::DROIT_ACCES);

			if (!$is_still_admin)
			{
				throw new UserException('Vous n\'êtes pas administrateur dans le fichier de sauvegarde fourni.');
			}
		}

		// On récupère la version pour plus tard
		$version = $db->querySingle('SELECT valeur FROM config WHERE cle=\'version\';');

		$db->close();

		$backup = str_replace('.sqlite', date('.Y-m-d-His') . '.avant_restauration.sqlite', DB_FILE);
		
		if (!rename(DB_FILE, $backup))
		{
			throw new \RuntimeException('Unable to backup current DB file.');
		}

		if (!copy($file, DB_FILE))
		{
			rename($backup, DB_FILE);
			throw new \RuntimeException('Unable to copy backup DB to main location.');
		}

		if ($version != garradin_version())
		{
			return self::NEED_UPGRADE;
		}

		return true;
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
		return (int) $db->simpleQuerySingle('SELECT SUM(taille) FROM fichiers_contenu;');
	}
}