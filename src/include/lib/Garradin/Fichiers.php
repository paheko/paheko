<?php

namespace Garradin;

use KD2\Image;
use Garradin\Membres\Session;

class Fichiers
{
	public $id;
	public $nom;
	public $type;
	public $image;
	public $datetime;
	public $hash;
	public $taille;
	public $id_contenu;

	/**
	 * Tailles de miniatures autorisées, pour ne pas avoir 500 fichiers générés avec 500 tailles différentes
	 * @var array
	 */
	protected static $allowed_thumb_sizes = [200, 500];

	const LIEN_COMPTA = 'compta_journal';
	const LIEN_WIKI = 'wiki_pages';
	const LIEN_MEMBRES = 'membres';

	/**
	 * Renvoie l'URL vers un fichier
	 * @param  integer $id   Numéro du fichier
	 * @param  string  $nom  Nom de fichier avec extension
	 * @param  integer $size Taille de la miniature désirée (pour les images)
	 * @return string        URL du fichier
	 */
	static public function _getURL($id, $nom, $size = false)
	{
		$url = WWW_URL . 'f/' . base_convert((int)$id, 10, 36) . '/' . $nom;

		if ($size)
		{
			$url .= '?' . self::_findThumbSize($size) . 'px';
		}

		return $url;
	}

	/**
	 * Renvoie la taille de miniature la plus proche de la taille demandée
	 * @param  integer $size Taille demandée
	 * @return integer       Taille possible
	 */
	static protected function _findThumbSize($size)
	{
		$size = (int) $size;

		if (in_array($size, self::$allowed_thumb_sizes))
		{
			return $size;
		}

		foreach (self::$allowed_thumb_sizes as $s)
		{
			if ($s >= $size)
			{
				return $s;
			}
		}

		return max(self::$allowed_thumb_sizes);
	}

	/**
	 * Constructeur de l'objet pour un fichier
	 * @param integer $id Numéro unique du fichier
	 */
	public function __construct($id, $data = null)
	{
		if (is_null($data))
		{
			$data = DB::getInstance()->first('SELECT fichiers.*, fc.hash, fc.taille,
				strftime(\'%s\', datetime) AS datetime
				FROM fichiers INNER JOIN fichiers_contenu AS fc ON fc.id = fichiers.id_contenu
				WHERE fichiers.id = ?;', (int)$id);
		}

		if (!$data)
		{
			throw new \InvalidArgumentException('Ce fichier n\'existe pas.');
		}

		foreach ($data as $key=>$value)
		{
			$this->$key = $value;
		}
	}

	/**
	 * Renvoie l'adresse d'accès au fichier
	 * @param  boolean $size Taille éventuelle de la miniature demandée
	 * @return string        URL d'accès au fichier
	 */
	public function getURL($size = false)
	{
		return self::_getURL($this->id, $this->nom, $size);
	}

	/**
	 * Lier un fichier à un contenu
	 * @param  string $type       Type de contenu (constantes LIEN_*)
	 * @param  integer $foreign_id ID du contenu lié
	 * @return boolean TRUE en cas de succès
	 */
	public function linkTo($type, $foreign_id)
	{
		$db = DB::getInstance();
		$check = [self::LIEN_MEMBRES, self::LIEN_WIKI, self::LIEN_COMPTA];

		if (!in_array($type, $check))
		{
			throw new \LogicException('Type de lien de fichier inconnu.');
		}

		// Vérifier que le fichier n'est pas déjà lié à un autre type
		$query = [];

		foreach ($check as $check_type)
		{
			// Ne pas chercher dans le type qu'on veut lier
			if ($check_type == $type)
			{
				continue;
			}

			$query[] = sprintf('SELECT 1 FROM fichiers_%s WHERE fichier = %d', $check_type, $this->id);
		}

		$query = implode(' UNION ', $query) . ';';

		if ($db->firstColumn($query))
		{
			throw new \LogicException('Ce fichier est déjà lié à un autre contenu : ' . $check_type);
		}

		return $db->preparedQuery('INSERT OR IGNORE INTO fichiers_' . $type . ' (fichier, id) VALUES (?, ?);',
			[(int)$this->id, (int)$foreign_id]);
	}

	/**
	 * Vérifie que l'utilisateur a bien le droit d'accéder à ce fichier
	 * @param  mixed   $user Tableau contenant les infos sur l'utilisateur connecté, provenant de Session::getUser, ou false
	 * @return boolean       TRUE si l'utilisateur a le droit d'accéder au fichier, sinon FALSE
	 */
	public function checkAccess(Session $session)
	{
		$config = Config::getInstance();

		if ($config->get('image_fond') == $this->id)
		{
			return true;
		}

		$db = DB::getInstance();

		// On regarde déjà si le fichier n'est pas lié au wiki
		$query = sprintf('SELECT wp.droit_lecture FROM fichiers_%s AS link
			INNER JOIN wiki_pages AS wp ON wp.id = link.id
			WHERE link.fichier = ? LIMIT 1;', self::LIEN_WIKI);
		$wiki = $db->firstColumn($query, (int)$this->id);

		// Page wiki publique, aucune vérification à faire, seul cas d'accès à un fichier en dehors de l'espace admin
		if ($wiki !== false && $wiki == Wiki::LECTURE_PUBLIC)
		{
			return true;
		}
			
		// Pas d'utilisateur connecté, pas d'accès aux fichiers de l'espace admin
		if (!$session->isLogged())
		{
			return false;
		}

		$user = $session->getUser();

		if ($wiki !== false)
		{
			// S'il n'a même pas droit à accéder au wiki c'est mort
			if (!$session->canAccess('wiki', Membres::DROIT_ACCES))
			{
				return false;
			}

			// On renvoie à l'objet Wiki pour savoir si l'utilisateur a le droit de lire ce fichier
			$_w = new Wiki;
			$_w->setRestrictionCategorie($user->id_categorie, $user->droit_wiki);
			return $_w->canReadPage($wiki);
		}

		// On regarde maintenant si le fichier est lié à la compta
		$query = sprintf('SELECT 1 FROM fichiers_%s WHERE fichier = ? LIMIT 1;', self::LIEN_COMPTA);
		$compta = $db->firstColumn($query, (int)$this->id);

		if ($compta && $session->canAccess('compta', Membres::DROIT_ACCES))
		{
			// OK si accès à la compta
			return true;
		}

		// Enfin, si le fichier est lié à un membre
		$query = sprintf('SELECT id FROM fichiers_%s WHERE fichier = ? LIMIT 1;', self::LIEN_MEMBRES);
		$membre = $db->firstColumn($query, (int)$this->id);

		if ($membre !== false)
		{
			// De manière évidente, l'utilisateur a le droit d'accéder aux fichiers liés à son profil
			if ((int)$membre == $user->id)
			{
				return true;
			}

			// Pour voir les fichiers des membres il faut pouvoir les gérer
			if ($session->canAccess('membres', Membres::DROIT_ECRITURE))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Supprime le fichier
	 * @return boolean TRUE en cas de succès
	 */
	public function remove()
	{
		$db = DB::getInstance();
		$db->begin();
		$db->delete('fichiers_compta_journal', 'fichier = ?', (int)$this->id);
		$db->delete('fichiers_wiki_pages', 'fichier = ?', (int)$this->id);
		$db->delete('fichiers_membres', 'fichier = ?', (int)$this->id);

		$db->delete('fichiers', 'id = ?', (int)$this->id);

		// Suppression du contenu s'il n'est pas utilisé par un autre fichier
		if (!$db->firstColumn('SELECT 1 FROM fichiers WHERE id_contenu = ? AND id != ? LIMIT 1;', 
			(int)$this->id_contenu, (int)$this->id))
		{
			$db->delete('fichiers_contenu', 'id = ?', (int)$this->id_contenu);
		}

		$cache_id = 'fichiers.' . $this->id_contenu;
		
		Static_Cache::remove($cache_id);

		foreach (self::$allowed_thumb_sizes as $size)
		{
			Static_Cache::remove($cache_id . '.thumb.' . (int)$size);
		}

		return $db->commit();
	}

	/**
	 * Renvoie le chemin vers le fichier local en cache, et le crée s'il n'existe pas
	 * @return string Chemin local
	 */
	protected function getFilePathFromCache()
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

		return Static_Cache::getPath($cache_id);
	}

	/**
	 * Envoie le fichier au client HTTP
	 * @return void
	 */
	public function serve()
	{
		return $this->_serve($this->getFilePathFromCache(), $this->type, ($this->image ? false : $this->nom), $this->taille);
	}

	/**
	 * Envoie une miniature à la taille indiquée au client HTTP
	 * @return void
	 */
	public function serveThumbnail($width = self::TAILLE_MINIATURE)
	{
		if (!$this->image)
		{
			throw new \LogicException('Il n\'est pas possible de fournir une miniature pour un fichier qui n\'est pas une image.');
		}

		if (!in_array($width, self::$allowed_thumb_sizes))
		{
			throw new UserException('Cette taille de miniature n\'est pas autorisée.');
		}

		$cache_id = 'fichiers.' . $this->id_contenu . '.thumb.' . (int)$width;
		$path = Static_Cache::getPath($cache_id);

		// La miniature n'existe pas dans le cache statique, on la crée
		if (!Static_Cache::exists($cache_id))
		{
			$source = $this->getFilePathFromCache();

			(new Image($source))->resize($width)->save($path);
		}

		return $this->_serve($path, $this->type);
	}

	/**
	 * Servir un fichier local en HTTP
	 * @param  string $path Chemin vers le fichier local
	 * @param  string $type Type MIME du fichier
	 * @param  string $name Nom du fichier avec extension
	 * @param  integer $size Taille du fichier en octets (facultatif)
	 * @return boolean TRUE en cas de succès
	 */
	protected function _serve($path, $type, $name = false, $size = null)
	{
		// Désactiver le cache
		header('Pragma: public');
		header('Expires: -1');
		header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');

		header('Content-Type: '.$type);

		if ($name)
		{
			header('Content-Disposition: attachment; filename="' . $name . '"');
		}
		
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

		if ($size)
		{
			header('Content-Length: '. (int)$size);
		}

		ob_clean();
		flush();

		// Sinon on envoie le fichier à la mano
		return readfile($path);
	}

	/**
	 * Vérifie si le hash fourni n'est pas déjà stocké
	 * Utile pour par exemple reconnaître un ficher dont le contenu est déjà stocké, et éviter un nouvel upload
	 * @param  string $hash Hash SHA1
	 * @return boolean      TRUE si le hash est déjà présent dans fichiers_contenu, FALSE sinon
	 */
	static public function checkHash($hash)
	{
		return (boolean) DB::getInstance()->firstColumn(
			'SELECT 1 FROM fichiers_contenu WHERE hash = ?;', 
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
		$db = DB::getInstance();

		array_walk($list, function (&$a) use ($db) {
			$a = $db->quote($a);
		});

		$query = sprintf('SELECT hash, 1 FROM fichiers_contenu WHERE hash IN (%s);', 
			implode(', ', $list));
		return $db->getAssoc($query);
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
	 * @return Fichiers
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

		$name = preg_replace('/\s+/', '_', $file['name']);
		$name = preg_replace('/[^\d\w._-]/ui', '', $name);

		return self::storeFile($name, $file['tmp_name']);
	}

	/**
	 * Upload de fichier à partir d'une chaîne en base64
	 * @param  string $name
	 * @param  string $content
	 * @return Fichiers
	 */
	static public function storeFromBase64($name, $content)
	{
		$content = base64_decode($content);
		return self::storeFile($name, null, $content);
	}

	/**
	 * Upload de fichier (interne)
	 * 
	 * @param  string $name
	 * @param  string $path Chemin du fichier
	 * @param  string $content Ou contenu du fichier
	 * @return Fichiers
	 */
	static protected function storeFile($name, $path = null, $content = null)
	{
		assert($path || $content);

		if ($path && !$content)
		{
			$hash = sha1_file($path);
			$size = filesize($path);
			$bytes = file_get_contents($path, false, null, -1, 1024);
		}
		else
		{
			$hash = sha1($content);
			$size = strlen($content);
			$bytes = substr($content, 0, 1024);
		}

		$type = \KD2\FileInfo::guessMimeType($bytes);

		if (!$type)
		{
			$ext = substr($name, strrpos($name, '.')+1);
			$ext = strtolower($ext);

			$type = \KD2\FileInfo::getMimeTypeFromFileExtension($ext);
		}

		$is_image = preg_match('/^image\/(?:png|jpe?g|gif)$/', $type);

		$db = DB::getInstance();

		$db->begin();

		// Il peut arriver que l'on renvoie ici un fichier déjà stocké, auquel cas, ne pas le re-stocker
		if (!($id_contenu = $db->firstColumn('SELECT id FROM fichiers_contenu WHERE hash = ?;', $hash)))
		{
			$db->insert('fichiers_contenu', [
				'hash'		=>	$hash,
				'taille'	=>	(int)$size,
				'contenu'	=>	[\SQLITE3_BLOB, $content ?: file_get_contents($path)],
			]);

			// FIXME: utiliser Sqlite3::openBlob pour écrire quand dispo dans PHP
			// cf. https://github.com/php/php-src/pull/2528

			$id_contenu = $db->lastInsertRowID();
		}

		$db->insert('fichiers', [
			'id_contenu'	=>	(int)$id_contenu,
			'nom'			=>	$name,
			'type'			=>	$type,
			'image'			=>	(int)$is_image,
		]);

		$db->commit();

		return new Fichiers($db->lastInsertRowID());
	}

	/**
	 * Envoie un fichier déjà stocké
	 * 
	 * @param  string $name Nom du fichier
	 * @param  string $hash Hash SHA1 du contenu du fichier
	 * @return object       Un objet Fichiers en cas de succès
	 */
	static public function uploadExistingHash($name, $hash)
	{
		$db = DB::getInstance();
		$name = preg_replace('/[^\d\w._-]/ui', '', $name);

		$file = $db->first('SELECT * FROM fichiers 
			INNER JOIN fichiers_contenu AS fc ON fc.id = fichiers.id_contenu AND fc.hash = ?;', trim($hash));

		if (!$file)
		{
			throw new UserException('Le fichier à copier n\'existe pas (aucun hash ne correspond à '.$hash.').');
		}

		$db->insert('fichiers', [
			'id_contenu' =>	(int)$file->id_contenu,
			'nom'        =>	$name,
			'type'       =>	$file->type,
			'image'      =>	(int)$file->image,
		]);

		return new Fichiers($db->lastInsertRowID());
	}

	/**
	 * Récupère la liste des fichiers liés à une ressource
	 * 
	 * @param  string  $type    Type de ressource
	 * @param  integer $id      Numéro de ressource
	 * @param  boolean $images  TRUE pour retourner seulement les images,
	 * FALSE pour retourner les fichiers sans images, NULL pour tout retourner
	 * @return array          Liste des fichiers
	 */
	static public function listLinkedFiles($type, $id, $images = false)
	{
		$check = [self::LIEN_MEMBRES, self::LIEN_WIKI, self::LIEN_COMPTA];

		if (!in_array($type, $check))
		{
			throw new \LogicException('Type de lien de fichier inconnu.');
		}

		$images = is_null($images) ? '' : ' AND image = ' . (int)$images;

		$query = sprintf('SELECT fichiers.*, c.hash, c.taille
			FROM fichiers 
			INNER JOIN fichiers_%s AS fwp ON fwp.fichier = fichiers.id
			INNER JOIN fichiers_contenu AS c ON c.id = fichiers.id_contenu
			WHERE fwp.id = ? %s
			ORDER BY fichiers.nom COLLATE NOCASE;', $type, $images);

		$files = DB::getInstance()->get($query, (int)$id);

		foreach ($files as &$file)
		{
			$file->url = self::_getURL($file->id, $file->nom);
			$file->thumb = $file->image ? self::_getURL($file->id, $file->nom, 200) : false;
		}

		return $files;
	}

	/**
	 * Enlève d'une liste de fichiers ceux qui sont mentionnés dans un texte wiki
	 * @param  array $files Liste de fichiers
	 * @param  string $text  texte wiki
	 * @return array        Un tableau qui ne contient pas les fichiers mentionnés dans $text
	 */
	static public function filterFilesUsedInText($files, $text)
	{
		$used = self::listFilesUsedInText($text);

		return array_filter($files, function ($row) use ($used) {
			return !in_array($row->id, $used);
		});
	}

	/**
	 * Renvoie une liste d'ID de fichiers mentionnées dans un texte wiki
	 * @param  string $text Texte wiki
	 * @return array       Liste des IDs de fichiers mentionnés
	 */
	static public function listFilesUsedInText($text)
	{
		preg_match_all('/<<?(?:fichier|image)\s*(?:\|\s*)?(\d+)/', $text, $match, PREG_PATTERN_ORDER);
		preg_match_all('/(?:fichier|image):\/\/(\d+)/', $text, $match2, PREG_PATTERN_ORDER);
		
		return array_merge($match[1], $match2[1]);
	}

	/**
	 * Callback utilisé pour l'extension <<fichier>> dans le wiki-texte
	 * @param array $args    Arguments passés à l'extension
	 * @param string $content Contenu éventuel (en mode bloc)
	 * @param object $skriv   Objet SkrivLite
	 */
	static public function SkrivFichier($args, $content, $skriv)
	{
		$id = $caption = null;

		foreach ($args as $value)
		{
			if (preg_match('/^\d+$/', $value) && !$id)
			{
				$id = (int)$value;
				break;
			}
			else
			{
				$caption = trim($value);
			}
		}

		if (empty($id))
		{
			return $skriv->parseError('/!\ Tag fichier : aucun numéro de fichier indiqué.');
		}

		try {
			$file = new Fichiers($id);
		}
		catch (\InvalidArgumentException $e)
		{
			return $skriv->parseError('/!\ Tag fichier : ' . $e->getMessage());
		}

		if (empty($caption))
		{
			$caption = $file->nom;
		}

		$out = '<aside class="fichier" data-type="'.$skriv->escape($file->type).'">';
		$out.= '<a href="'.$file->getURL().'" class="internal-file">'.$skriv->escape($caption).'</a> ';
		$out.= '<small>('.$skriv->escape(($file->type ? $file->type . ', ' : '') . Utils::format_bytes($file->taille)).')</small>';
		$out.= '</aside>';
		return $out;
	}

	/**
	 * Callback utilisé pour l'extension <<image>> dans le wiki-texte
	 * @param array $args    Arguments passés à l'extension
	 * @param string $content Contenu éventuel (en mode bloc)
	 * @param object $skriv   Objet SkrivLite
	 */
	static public function SkrivImage($args, $content, $skriv)
	{
		static $align_values = ['droite', 'gauche', 'centre'];

		$align = '';
		$id = $caption = null;

		foreach ($args as $value)
		{
			if (preg_match('/^\d+$/', $value) && !$id)
			{
				$id = (int)$value;
			}
			else if (in_array($value, $align_values) && !$align)
			{
				$align = $value;
			}
			else
			{
				$caption = $value;
			}
		}

		if (!$id)
		{
			return $skriv->parseError('/!\ Tag image : aucun numéro de fichier indiqué.');
		}

		try {
			$file = new Fichiers($id);
		}
		catch (\InvalidArgumentException $e)
		{
			return $skriv->parseError('/!\ Tag image : ' . $e->getMessage());
		}

		if (!$file->image)
		{
			return $skriv->parseError('/!\ Tag image : ce fichier n\'est pas une image.');
		}

		$out = '<a href="'.$file->getURL().'" class="internal-image">';
		$out .= '<img src="'.$file->getURL($align == 'centre' ? 500 : 200).'" alt="';

		if ($caption)
		{
			$out .= htmlspecialchars($caption, ENT_QUOTES, 'UTF-8');
		}

		$out .= '" /></a>';

		if (!empty($align))
		{
			$out = '<figure class="image ' . $align . '">' . $out;

			if ($caption)
			{
				$out .= '<figcaption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption>';
			}

			$out .= '</figure>';
		}

		return $out;
	}
}