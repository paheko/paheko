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

	/**
	 * Supprime l'image
	 * @return boolean TRUE en cas de succès
	 */
	public function remove()
	{
		$db = DB::getInstance();
		$db->exec('BEGIN;');
		$db->simpleExec('DELETE FROM fichiers_compta_journal WHERE fichier = ?;', (int)$this->id);
		$db->simpleExec('DELETE FROM fichiers_wiki_pages WHERE fichier = ?;', (int)$this->id);
		$db->simpleExec('DELETE FROM fichiers_membres WHERE fichier = ?;', (int)$this->id);
		$db->simpleExec('DELETE FROM fichiers_contenu WHERE id = ?;', (int)$this->id);
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
		$cache_id = 'fichiers.' . $this->id;

		// Le fichier n'existe pas dans le cache statique, on l'enregistre
		if (!Static_Cache::exists($cache_id))
		{
			$blob = DB::getInstance()->openBlob('fichiers_contenu', 'contenu', (int)$this->id);
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
	 * Upload du fichier par POST
	 * @param  array  $file  Caractéristiques du fichier envoyé
	 * @param  string $titre Titre descriptif du fichier
	 * @return boolean TRUE en cas de succès
	 */
	static public function upload($file, $titre)
	{
		// FIXME traiter les images envoyées redimensionnées par javascript (base64)

		$name = '...';
		// FIXME name sanitization

		if (preg_match('/\.(?:php\d*|cgi|pl|perl|jsp|asp|py|exe|com|bat|vb[se]?|chm|pif|reg|ws[cfh]|scr|asp)$/i', $name))
		{
			throw new UserException('Extension de fichier interdite.');
		}

		$ext = substr($name, strrpos($name, '.')+1);
		$ext = strtolower($ext);

		if (!array_key_exists($ext, $this->allowed_files))
		{
			throw new UserException('Ce type de fichier n\'est pas autorisé.');
		}

		$bytes = file_get_contents($path, false, null, -1, 1024);
		$type = \KD2\FileInfo::guessMimeType($bytes);

		if (!$type)
		{
			throw new UserException('Type de fichier inconnu.');
		}
	}
}