<?php

namespace Garradin;

class Fichiers
{
	public $type;
	public $nom;
	public $datetime;
	public $hash;
	public $taille;
	public $id;

	const LIEN_COMPTA = 'compta_journal';
	const LIEN_WIKI = 'wiki_pages';
	const LIEN_MEMBRES = 'membres';

	public function __construct($id)
	{
		$data = DB::getInstance()->simpleQuerySingle('SELECT *, strftime(\'%s\', datetime) AS datetime
			FROM fichiers WHERE id = ?;', true, (int)$id);

		foreach ($data as $key=>$value)
		{
			$this->$key = $value;
		}
	}	

	/**
	 * Envoie une miniature à la taille indiquée au client HTTP
	 * @param  integer $width  Largeur
	 * @param  integer $height Hauteur
	 * @param  boolean $crop   TRUE si on doit cropper aux dimensions indiquées
	 * @return void
	 */
	public function getThumbnail($width, $height, $crop = false)
	{
	}

	public function linkTo($type, $foreign_id)
	{
		$db = DB::getInstance();
		$check = [self::LIEN_MEMBRES, self::LIEN_WIKI, self::LIEN_COMPTA];

		if (!in_array($type, $check))
		{
			throw new \LogicException('Type de lien de fichier inconnu.');
		}

		unset($check[array_search($type, $check)]);
		
		foreach ($check as $check_type)
		{
			if ($db->simpleQuerySingle('SELECT 1 FROM fichiers_' . $check_type . ' WHERE fichier = ?;', false, (int)$this->id))
			{
				throw new \LogicException('Ce fichier est déjà lié à un autre contenu : ' . $check_type);
			}
		}

		return $db->simpleExec('INSERT OR IGNORE INTO fichiers_' . $type . ' (fichier, id) VALUES (?, ?);',
			(int)$this->id, (int)$foreign_id);
	}

	/**
	 * Supprime le fichier
	 * @return boolean TRUE en cas de succès
	 */
	public function remove()
	{
		$db = DB::getInstance();
		$db->exec('BEGIN;');
		$db->simpleExec('DELETE FROM fichiers_compta_journal WHERE fichier = ?;', (int)$this->id);
		$db->simpleExec('DELETE FROM fichiers_wiki_pages WHERE fichier = ?;', (int)$this->id);
		$db->simpleExec('DELETE FROM fichiers_membres WHERE fichier = ?;', (int)$this->id);

		// Suppression du contenu s'il n'est pas utilisé par un autre fichier
		if (!($id_contenu = $db->simpleQuerySingle('SELECT id_contenu FROM fichiers AS f1 INNER JOIN fichiers AS f2 
			ON f1.id_contenu = f2.id_contenu AND f1.id != f2.id WHERE f2.id = ?;', false, (int)$this->id)))
		{
			$db->simpleExec('DELETE FROM fichiers_contenu WHERE id = ?;', (int)$id_contenu);
		}

		$db->simpleExec('DELETE FROM fichiers WHERE id = ?;', (int)$this->id);

		return $db->exec('END;');
	}

	/**
	 * Modifie les informations du fichier
	 * @param  string $nom   Le nom du fichier (avec extension)
	 * @return boolean TRUE en cas de succès
	 */
	public function edit($nom)
	{

	}

	/**
	 * Envoie le fichier au client HTTP
	 * @return void
	 */
	public function serve()
	{
		// Le cache est géré par ID contenu, pas ID fichier, pour minimiser l'espace disque utilisé
		$cache_id = 'fichiers.' . $this->id_contenu;

		// Le fichier n'existe pas dans le cache statique, on l'enregistre
		if (!Static_Cache::exists($cache_id))
		{
			$blob = DB::getInstance()->openBlob('fichiers_contenu', 'contenu', (int)$this->id_contenu);
			Static_Cache::storeFromPointer($cache_id, $blob);
			fclose($blob);
		}

		$path = Static_Cache::getPath($cache_id);

		// Désactiver le cache
		header('Pragma: public');
		header('Expires: -1');
		header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');

		header('Content-Disposition: attachment; filename="' . $this->nom . '"');
		
		// Utilisation de XSendFile si disponible
		if (ENABLE_XSENDFILE && isset($_SERVER['SERVER_SOFTWARE']))
		{
			if (stristr($_SERVER['SERVER_SOFTWARE'], 'apache') 
				&& function_exists('apache_get_modules') 
				&& in_array('mod_xsendfile', apache_get_modules()))
			{
				header('X-Sendfile: ' . $path);
				return true;
			}
			else if (stristr($_SERVER['SERVER_SOFTWARE'], 'lighttpd'))
			{
				header('X-Sendfile: ' . $path);
				return true;
			}
		}

		// Désactiver gzip
		if (function_exists('apache_setenv'))
		{
			@apache_setenv('no-gzip', 1);
		}

		@ini_set('zlib.output_compression', 'Off');

		header('Content-Length: '. (int)$this->taille);

		ob_clean();
		flush();

		// Sinon on envoie le fichier à la mano
		readfile($path);
	}

	/**
	 * Vérifie si le hash fourni n'est pas déjà stocké
	 * Utile pour par exemple reconnaître un ficher dont le contenu est déjà stocké, et éviter un nouvel upload
	 * @param  string $hash Hash SHA1
	 * @return boolean      TRUE si le hash est déjà présent dans fichiers_contenu, FALSE sinon
	 */
	static public function checkHash($hash)
	{
		return (boolean) DB::getInstance()->simpleQuerySingle(
			'SELECT 1 FROM fichiers_contenu WHERE hash = ?;', 
			false, 
			trim(strtolower($hash))
		);
	}

	/**
	 * Retourne un tableau de hash trouvés dans la DB parmi une liste de hash fournis
	 * @param  array  $list Liste de hash à vérifier
	 * @return array        Liste des hash trouvés
	 */
	static public function checkHashList($list)
	{
		$hash_list = '';
		$db = DB::getInstance();

		foreach ($list as $hash)
		{
			$hash_list .= '\'' . $db->escapeString($hash) . '\',';
		}

		$hash_list = substr($hash_list, 0, -1);

		return $db->queryFetchAssoc('SELECT hash, 1
			FROM fichiers_contenu WHERE hash IN (' . $hash_list . ');');
	}

	/**
	 * Récupération du message d'erreur
	 * @param  integer $error Code erreur du $_FILE
	 * @return string Message d'erreur
	 */
	static public function getErrorMessage($error)
	{
		switch ($error)
		{
			case UPLOAD_ERR_INI_SIZE:
				return 'Le fichier excède la taille permise par la configuration du serveur.';
			case UPLOAD_ERR_FORM_SIZE:
				return 'Le fichier excède la taille permise par le formulaire.';
			case UPLOAD_ERR_PARTIAL:
				return 'L\'envoi du fichier a été interrompu.';
			case UPLOAD_ERR_NO_FILE:
				return 'Aucun fichier n\'a été reçu.';
			case UPLOAD_ERR_NO_TMP_DIR:
				return 'Pas de répertoire temporaire pour stocker le fichier.';
			case UPLOAD_ERR_CANT_WRITE:
				return 'Impossible d\'écrire le fichier sur le disque du serveur.';
			case UPLOAD_ERR_EXTENSION:
				return 'Une extension du serveur a interrompu l\'envoi du fichier.';
			default:
				return 'Erreur inconnue: ' . $error;
		}
	}

	/**
	 * Upload du fichier par POST
	 * @param  array  $file  Caractéristiques du fichier envoyé
	 * @return boolean TRUE en cas de succès
	 */
	static public function upload($file)
	{
		if (!empty($file['error']))
		{
			throw new UserException(self::getErrorMessage($file['error']));
		}

		if (empty($file['size']) || empty($file['name']))
		{
			throw new UserException('Fichier reçu invalide : vide ou sans nom de fichier.');
		}

		if (!is_uploaded_file($file['tmp_name']))
		{
			throw new \RuntimeException('Le fichier n\'a pas été envoyé de manière conventionnelle.');
		}

		$name = preg_replace('/[^\d\w._-]/ui', '', $file['name']);

		$bytes = file_get_contents($file['tmp_name'], false, null, -1, 1024);
		$type = \KD2\FileInfo::guessMimeType($bytes);

		if (!$type)
		{
			$ext = substr($name, strrpos($name, '.')+1);
			$ext = strtolower($ext);

			$type = \KD2\FileInfo::getMimeTypeFromFileExtension($ext);
		}

		$is_image = preg_match('/^image\//', $type);

		$hash = sha1_file($file['tmp_name']);
		$size = filesize($file['tmp_name']);

		$db = DB::getInstance();
		$db->exec('BEGIN;');

		$db->simpleInsert('fichiers_contenu', [
			'hash'		=>	$hash,
			'taille'	=>	(int)$size,
			'contenu'	=>	[\SQLITE3_BLOB, file_get_contents($file['tmp_name'])],
		]);

		$id_contenu = $db->lastInsertRowID();

		$db->simpleInsert('fichiers', [
			'id_contenu'	=>	(int)$id_contenu,
			'nom'			=>	$name,
			'type'			=>	$type,
			'image'			=>	(int)$is_image,
		]);

		$db->exec('END;');

		return new Fichiers($db->lastInsertRowID());
	}

	static public function uploadExistingHash($name, $hash)
	{
		$db = DB::getInstance();
		$name = preg_replace('/[^\d\w._-]/ui', '', $name);

		$file = $db->simpleQuerySingle('SELECT * FROM fichiers 
			INNER JOIN fichiers_contenu AS fc ON fc.id = fichiers.id_contenu AND fc.hash = ?;', true, trim($hash));

		if (!$file)
		{
			throw new UserException('Le fichier à copier n\'existe pas (aucun hash ne correspond à '.$hash.').');
		}

		$db->simpleInsert('fichiers', [
			'id_contenu'	=>	(int)$file['id_contenu'],
			'nom'			=>	$name,
			'type'			=>	$file['type'],
			'image'			=>	(int)$file['image'],
		]);

		return new Fichiers($db->lastInsertRowID());
	}
}