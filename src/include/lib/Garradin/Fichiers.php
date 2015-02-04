<?php

namespace Garradin;

class Fichiers
{
	protected $allowed_files_extensions = [
		'jpeg', 'jpg', 'jpe', 'gif', 'png', 'svg', 'svgz', 'psd', 'bmp', 'ico', // Images
		'pdf', 'txt', 'rtf', 'tex', 'lyx', 'html', 'epub', 'mobi', 'ps', 'xml', // Textes
		'sxw', 'sxc', 'sxd', 'sxi', 'sxf', 'odt', 'odg', 'odp', 'ods', 'odc', 'odf', // Libre Office
		'docx', 'xlsx', 'doc', 'xls', 'ppsx', 'pps', 'pptx', 'ppt', 'pub', // Microsoft
		'webm', 'mp4', 'flv', 'mkv', 'avi', 'mov', // Vidéos
		'mp3', 'm4a', 'aac', 'ogg', 'mid', // Audio
		'zip', 'rar', '7z', 'gz', 'xz', 'bz2', 'bz', 'tar', // Archives
		'sqlite', 'swf', // Divers
	];

	public $type;
	public $titre;
	public $nom;
	public $date;
	public $hash;
	public $taille;
	public $id;

	const LIEN_COMPTA = 'compta_journal';
	const LIEN_WIKI = 'wiki_pages';
	const LIEN_MEMBRES = 'membres';

	public function __construct($id)
	{
		$data = DB::getInstance()->simpleQuerySingle('SELECT *, strftime(\'%s\', date) AS date
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
	 * @param  string $titre Le titre du fichier
	 * @param  string $nom   Le nom du fichier (avec extension)
	 * @return boolean TRUE en cas de succès
	 */
	public function edit($titre, $nom)
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
	 * Upload du fichier par POST
	 * @param  array  $file  Caractéristiques du fichier envoyé
	 * @param  string $titre Titre descriptif du fichier
	 * @return boolean TRUE en cas de succès
	 */
	static public function upload($file, $titre, $allow_anything = false)
	{
		// FIXME traiter les images envoyées redimensionnées par javascript (base64)

		$name = '...';
		// FIXME name sanitization

		if (!$allow_anything && preg_match('/\.(?:php\d*|cgi|pl|perl|jsp|asp|py|exe|com|bat|vb[se]?|chm|pif|reg|ws[cfh]|scr|asp)$/i', $name))
		{
			throw new UserException('Extension de fichier interdite.');
		}

		$ext = substr($name, strrpos($name, '.')+1);
		$ext = strtolower($ext);

		if (!$allow_anything && !array_key_exists($ext, $this->allowed_files))
		{
			throw new UserException('Ce type de fichier n\'est pas autorisé.');
		}

		$bytes = file_get_contents($path, false, null, -1, 1024);
		$type = \KD2\FileInfo::guessMimeType($bytes);

		if (!$allow_anything && !$type)
		{
			throw new UserException('Type de fichier inconnu.');
		}
	}
}