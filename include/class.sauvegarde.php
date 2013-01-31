<?php

namespace Garradin;

class Sauvegarde
{
	const NEED_UPGRADE = 'nu';

	public function getList()
	{
		$out = array();
		$dir = dir(GARRADIN_ROOT);

		while ($file = $dir->read())
		{
			if ($file[0] != '.' && is_file(GARRADIN_ROOT . '/' . $file) && preg_match('![\w\d._-]+\.sqlite$!i', $file))
			{
				$out[] = $file;
			}
		}

		$dir->close();

		return $out;
	}

	public function create()
	{
		$this->syncDB();
		$backup = str_replace('.sqlite', date('.Y-m-d-H-i') . '.sqlite', GARRADIN_DB_FILE);
		copy(GARRADIN_DB_FILE, $backup);
		return basename($backup);
	}

	protected function syncDB()
	{
		$db = DB::getInstance();
		$db->exec('END;');
		return true;
	}

	public function remove($file)
	{
		if (preg_match('!\.\.+|/!', $file) || !preg_match('!^[\w\d._-]+$!i', $file))
		{
			throw new UserException('Nom de fichier non valide.');
		}

		return unlink(GARRADIN_ROOT . '/' . $file);
	}

	public function dump()
	{
		$this->syncDB();
		$in = fopen(GARRADIN_DB_FILE, 'r');
        $out = fopen('php://output', 'w');

        while (!feof($in))
        {
        	fwrite($out, fread($in, 8192));
        }

        fclose($in);
        fclose($out);
        return true;
	}

	public function restoreFromLocal($file)
	{
		if (preg_match('!\.\.+|/!', $file) || !preg_match('!^[\w\d._-]+$!i', $file))
		{
			throw new UserException('Nom de fichier non valide.');
		}

		return $this->restoreDB(GARRADIN_ROOT . '/' . $file);
	}

	public function restoreFromUpload($file)
	{
		if (empty($file['size']) || empty($file['tmp_name']) || !empty($file['error']))
		{
			throw new UserException('Le fichier n\'a pas été correctement envoyé. Essayer de le renvoyer à nouveau.');
		}

		$r = $this->restoreDB($file['tmp_name']);

		if ($r)
		{
			unlink($file['tmp_name']);
		}

		return $r;
	}

	protected function restoreDB($file)
	{
		try {
			$db = new SQLite3($file, SQLITE3_OPEN_READONLY);
		}
		catch (Exception $e)
		{
			throw new UserException('Le fichier fourni n\'est pas une base de données valide. ' .
				'Message d\'erreur de SQLite : ' . $e->getMessage());
		}

		$check = $db->querySingle('PRAGMA integrity_check;');

		if (strtolower(trim($check)) != 'ok')
		{
			throw new UserException('Le fichier fourni est corrompu. SQLite a trouvé ' . $check . ' erreurs.');
		}

		// Une vérification de base quand même
		$table = $db->querySingle('SELECT 1 FROM sqlite_master WHERE type=\'table\';');

		if (!$table)
		{
			throw new UserException('Le fichier fourni ne semble pas contenir de données liées à Garradin.');
		}

		$version = $db->querySingle('SELECT version FROM config;');

		$db->close();

		$backup = str_replace('.sqlite', date('.Y-m-d-H-i') . '.pre-restore.sqlite', GARRADIN_DB_FILE);
		
		if (!rename(GARRADIN_DB_FILE, $backup))
		{
			throw new \RuntimeException('Unable to backup current DB file.');
		}

		if (!copy($file, GARRADIN_DB_FILE))
		{
			throw new \RuntimeException('Unable to copy backup DB to main location.');
		}

		if ($version != garradin_version())
		{
			return self::NEED_UPGRADE;
		}

		return true;
	}

}

?>