<?php

namespace Garradin\Entities\Files;

use KD2\Graphics\Image;
use KD2\DB\EntityManager as EM;

use Garradin\DB;
use Garradin\Entity;
use Garradin\UserException;
use Garradin\Membres\Session;
use Garradin\Membres;
use Garradin\Static_Cache;
use Garradin\Utils;
use Garradin\Entities\Web\Page;

use Garradin\Files\Files;

use const Garradin\{WWW_URL, ENABLE_XSENDFILE};

class File extends Entity
{
	const TABLE = 'files';

	protected $id;
	protected $folder_id;
	protected $name;
	protected $type;
	protected $image;
	protected $public;
	protected $size;
	protected $hash;

	protected $storage;
	protected $storage_path;

	protected $created;

	protected $author_id;

	protected $_types = [
		'id'           => 'int',
		'folder_id'    => '?int',
		'name'         => 'string',
		'type'         => '?string',
		'public'       => 'int',
		'image'        => 'int',
		'size'         => 'int',
		'hash'         => 'string',
		'storage'      => '?string',
		'storage_path' => '?string',
		'created'      => 'DateTime',
		'author_id'    => '?int',
	];

	/**
	 * Tailles de miniatures autorisées, pour ne pas avoir 500 fichiers générés avec 500 tailles différentes
	 * @var array
	 */
	const ALLOWED_THUMB_SIZES = [200, 500, 1200];

	// Link to another file (ie. image included in a HTML file)
	const LINK_FILE = 'file_id';
	const LINK_USER = 'user_id';
	const LINK_TRANSACTION = 'transaction_id';
	const LINK_CONFIG = 'config';
	const LINK_WEB_PAGE = 'web_page_id';

	const THUMB_CACHE_ID = 'file.thumb.%d.%d';

	public function selfCheck(): void
	{
		parent::selfCheck();
	}

	public function delete(): bool
	{
		$return = parent::delete();

		// clean up thumbs
		foreach (self::ALLOWED_THUMB_SIZES as $size)
		{
			Static_Cache::remove(sprintf(self::THUMB_CACHE_ID, $this->id(), $size));
		}

		return $return;
	}

	public function save(): bool
	{
		$return = parent::save();

		// Store content in search table
		if ($return && substr($this->type, 0, 5) == 'text/') {
			$content = Files::callStorage('fetch', $this);

			if ($this->type == Page::FILE_TYPE_HTML) {
				$content = strip_tags($content);
			}

			if ($this->type == Page::FILE_TYPE_ENCRYPTED) {
				$content = 'Contenu chiffré';
			}

			DB::getInstance()->preparedQuery('INSERT OR REPLACE INTO files_search (id, content) VALUES (?, ?);', $this->id(), $content);
		}

		return $return;
	}

	public function store(string $source_path = null, $source_content = null): self
	{
		if ($source_path && !$source_content)
		{
			$this->set('hash', sha1_file($source_path));
			$this->set('size', filesize($source_path));
		}
		else
		{
			$this->set('hash', sha1($source_content));
			$this->set('size', strlen($source_content));
		}

		// Check that it's a real image
		if ($this->image) {
			try {
				if ($source_path && !$source_content) {
					$i = new Image($source_path);
				}
				else {
					$i = Image::createFromBlob($source_content);
				}

				// Recompress PNG files from base64, assuming they are coming
				// from JS canvas which doesn't know how to gzip (d'oh!)
				if ($i->format() == 'png' && null !== $source_content) {
					$source_content = $i->output('png', true);
					$this->set('hash', sha1($source_content));
					$this->set('size', strlen($source_content));
				}

				unset($i);
			}
			catch (\RuntimeException $e) {
				if (strstr($e->getMessage(), 'No suitable image library found')) {
					throw new \RuntimeException('Le serveur n\'a aucune bibliothèque de gestion d\'image installée, et ne peut donc pas accepter les images. Installez Imagick ou GD.');
				}

				throw new UserException('Fichier image invalide');
			}
		}

		if (!Files::callStorage('store', $this, $source_path, $source_content)) {
			throw new UserException('Le fichier n\'a pas pu être enregistré.');
		}

		return $this;
	}

	static protected function create(string $name, string $source_path = null, $source_content = null): self
	{
		assert($source_path || $source_content);

		$finfo = \finfo_open(\FILEINFO_MIME_TYPE);
		$file = new self;
		$file->name = $name;

		if ($source_path && !$source_content) {
			$file->type = finfo_file($finfo, $source_path);
		}
		else {
			$file->type = finfo_buffer($finfo, $source_content);
		}

		$file->image = preg_match('/^image\/(?:png|jpe?g|gif)$/', $file->type);

		$db = DB::getInstance();

		$db->begin();

		$file->store($source_path, $source_content);
		$file->save();

		$db->commit();
		return $file;
	}

	/**
	 * Upload de fichier à partir d'une chaîne en base64
	 * @param  string $name
	 * @param  string $content
	 * @return File
	 */
	static public function storeFromBase64(string $name, string $encoded_content): self
	{
		$content = base64_decode($encoded_content);
		return self::create($name, null, $content);
	}

	/**
	 * Upload du fichier par POST
	 */
	static public function upload(string $key): self
	{
		if (!isset($_FILES[$key]) || !is_array($_FILES[$key])) {
			throw new UserException('Aucun fichier reçu');
		}

		$file = $_FILES[$key];

		if (!empty($file['error'])) {
			throw new UserException(self::getErrorMessage($file['error']));
		}

		if (empty($file['size']) || empty($file['name'])) {
			throw new UserException('Fichier reçu invalide : vide ou sans nom de fichier.');
		}

		if (!is_uploaded_file($file['tmp_name'])) {
			throw new \RuntimeException('Le fichier n\'a pas été envoyé de manière conventionnelle.');
		}

		$name = preg_replace('/\s+/', '_', $file['name']);
		$name = preg_replace('/[^\d\w._-]/ui', '', $name);

		return self::create($name, $file['tmp_name']);
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

	public function url(?int $size = null): string
	{
		return self::getFileURL($this->id, $this->name, $this->hash, $size);
	}

	public function thumb_url(): string
	{
		return $this->url(min(self::ALLOWED_THUMB_SIZES));
	}

	/**
	 * Renvoie l'URL vers un fichier
	 * @param  integer $id   Numéro du fichier
	 * @param  string  $name  Nom de fichier avec extension
	 * @param  integer $size Taille de la miniature désirée (pour les images)
	 * @return string        URL du fichier
	 */
	static public function getFileURL(int $id, string $name, string $hash, ?int $size = null): string
	{
		$url = sprintf('%sf/%s/%s?', WWW_URL, base_convert((int)$id, 10, 36), $name);

		if ($size)
		{
			$url .= self::_findNearestThumbSize($size) . 'px&';
		}

		$url .= substr($hash, 0, 10);

		return $url;
	}

	/**
	 * Renvoie la taille de miniature la plus proche de la taille demandée
	 * @param  integer $size Taille demandée
	 * @return integer       Taille possible
	 */
	static protected function _findNearestThumbSize($size)
	{
		$size = (int) $size;

		if (in_array($size, self::ALLOWED_THUMB_SIZES))
		{
			return $size;
		}

		foreach (self::ALLOWED_THUMB_SIZES as $s)
		{
			if ($s >= $size)
			{
				return $s;
			}
		}

		return max(self::ALLOWED_THUMB_SIZES);
	}

	/**
	 * Lier un fichier à un contenu
	 * @param  string $type       Type de contenu (constantes LINK_*)
	 * @param  integer $foreign_id ID du contenu lié
	 * @return boolean TRUE en cas de succès
	 */
	public function linkTo(string $type, int $foreign_id): bool
	{
		$db = DB::getInstance();
		static $types = [self::LINK_WEB_PAGE, self::LINK_FILE, self::LINK_TRANSACTION, self::LINK_USER, self::LINK_CONFIG];

		if (!in_array($type, $types)) {
			throw new \InvalidArgumentException('Unknown file link type.');
		}

		if ($db->test('files_links', 'id = ?', $this->id())) {
			throw new \LogicException('This file is already linked to something else');
		}

		$sql = sprintf('INSERT OR IGNORE INTO files_links (id, %s) VALUES (?, ?);', $type);

		return $db->preparedQuery($sql, [$this->id, $foreign_id]);
	}

	public function getLinkedId(string $type): ?int
	{
		static $types = [self::LINK_WEB_PAGE, self::LINK_FILE, self::LINK_TRANSACTION, self::LINK_USER, self::LINK_CONFIG];

		if (!in_array($type, $types)) {
			throw new \InvalidArgumentException('Unknown file link type.');
		}

		return DB::getInstance()->firstColumn(sprintf('SELECT %s FROM files_links WHERE id = %d;', $type, $this->id()));
	}

	public function listLinked(): array
	{
		return EM::getInstance(File::class)->all('SELECT f.* FROM files f INNER JOIN files_links l ON l.id = f.id WHERE l.file_id = ?;', $this->id());
	}

	/**
	 * Envoie le fichier au client HTTP
	 */
	public function serve(?Session $session = null): void
	{
		if (!$this->checkReadAccess($session)) {
			header('HTTP/1.1 403 Forbidden', true, 403);
			throw new UserException('Vous n\'avez pas accès à ce fichier.');
			return;
		}

		$path = Files::callStorage('getPath', $this);
		$content = null === $path ? Files::callStorage('fetch', $this) : null;

		$this->_serve($path, $content);
	}

	/**
	 * Envoie une miniature à la taille indiquée au client HTTP
	 */
	public function serveThumbnail(?Session $session = null, ?int $width = null): void
	{
		if (!$this->checkReadAccess($session)) {
			header('HTTP/1.1 403 Forbidden', true, 403);
			throw new UserException('Accès interdit');
			return;
		}

		if (!$this->image) {
			throw new UserException('Il n\'est pas possible de fournir une miniature pour un fichier qui n\'est pas une image.');
		}

		if (!$width) {
			$width = reset(self::ALLOWED_THUMB_SIZES);
		}

		if (!in_array($width, self::ALLOWED_THUMB_SIZES)) {
			throw new UserException('Cette taille de miniature n\'est pas autorisée.');
		}

		$cache_id = sprintf(self::THUMB_CACHE_ID, $this->id(), $width);
		$destination = Static_Cache::getPath($cache_id);

		// La miniature n'existe pas dans le cache statique, on la crée
		if (!Static_Cache::exists($cache_id))
		{
			try {
				if ($path = Files::callStorage('getPath', $this)) {
					(new Image($path))->resize($width)->save($destination);
				}
				elseif ($content = Files::callStorage('fetch', $this)) {
					Image::createFromBlob($content)->resize($width)->save($destination);
				}
				else {
					throw new \RuntimeException('Unable to fetch file');
				}
			}
			catch (\RuntimeException $e) {
				throw new UserException('Impossible de créer la miniature');
			}
		}

		$this->_serve($destination, null);
	}

	/**
	 * Servir un fichier local en HTTP
	 * @param  string $path Chemin vers le fichier local
	 * @param  string $type Type MIME du fichier
	 * @param  string $name Nom du fichier avec extension
	 * @param  integer $size Taille du fichier en octets (facultatif)
	 * @return boolean TRUE en cas de succès
	 */
	protected function _serve(?string $path, ?string $content): void
	{
		if ($this->public) {
			Utils::HTTPCache($this->hash, $this->created->getTimestamp());
		}
		else {
			// Disable browser cache
			header('Pragma: private');
			header('Expires: -1');
			header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0');
		}

		header(sprintf('Content-Type: %s', $this->type));
		header(sprintf('Content-Disposition: inline; filename="%s"', $this->name));

		// Utilisation de XSendFile si disponible
		if (null !== $path && ENABLE_XSENDFILE && isset($_SERVER['SERVER_SOFTWARE']))
		{
			if (stristr($_SERVER['SERVER_SOFTWARE'], 'apache') 
				&& function_exists('apache_get_modules') 
				&& in_array('mod_xsendfile', apache_get_modules()))
			{
				header('X-Sendfile: ' . $path);
				return;
			}
			else if (stristr($_SERVER['SERVER_SOFTWARE'], 'lighttpd'))
			{
				header('X-Sendfile: ' . $path);
				return;
			}
		}

		// Désactiver gzip
		if (function_exists('apache_setenv'))
		{
			@apache_setenv('no-gzip', 1);
		}

		@ini_set('zlib.output_compression', 'Off');

		header(sprintf('Content-Length: %d', $this->size));

		if (@ob_get_length()) {
			@ob_clean();
		}

		flush();

		if (null !== $path) {
			readfile($path);
		}
		else {
			echo $content;
		}
	}

	public function fetch()
	{
		return Files::callStorage('fetch', $this);
	}

	public function checkReadAccess(Session $session): bool
	{
		// Web and config files should be marked as public when not in draft
		if ($this->public) {
			return true;
		}

		$link = DB::getInstance()->first('SELECT * FROM files_links WHERE id = ?;', $this->id());

		if (!$link) {
			return false;
		}

		// If it's linked to a file, then we want to know what the parent file is linked to
		if ($link->{self::LINK_FILE}) {
			return Files::get((int)$link->{self::LINK_FILE})->checkReadAccess($session);
		}

		if ($link->{self::LINK_TRANSACTION} && $session->canAccess(Session::SECTION_ACCOUNTING, Membres::DROIT_ACCES)) {
			return true;
		}
		// The user can access his own profile files
		else if ($link->{self::LINK_USER} && $link->{self::LINK_USER} == $session->getUser()->id) {
			return true;
		}
		// Only users able to manage users can see their profile files
		else if ($link->{self::LINK_USER} && $session->canAccess(Session::SECTION_USERS, Membres::DROIT_ECRITURE)) {
			return true;
		}

		return $session->canAccess(Session::SECTION_DOCUMENTS, Membres::DROIT_ACCES);
	}
}
