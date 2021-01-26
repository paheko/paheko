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
	protected $context;
	protected $context_ref;
	protected $name;
	protected $type;
	protected $image;
	protected $size;
	protected $hash;

	protected $created;
	protected $modified;

	protected $author_id;

	protected $_types = [
		'id'           => 'int',
		'context'      => 'string',
		'context_ref'  => '?int|string',
		'name'         => 'string',
		'type'         => '?string',
		'image'        => 'int',
		'size'         => 'int',
		'hash'         => 'string',
		'created'      => 'DateTime',
		'modified'     => 'DateTime',
		'author_id'    => '?int',
	];

	protected $_parent;

	/**
	 * Tailles de miniatures autorisées, pour ne pas avoir 500 fichiers générés avec 500 tailles différentes
	 * @var array
	 */
	const ALLOWED_THUMB_SIZES = [200, 500, 1200];

	const FILE_TYPE_HTML = 'text/html';
	const FILE_TYPE_ENCRYPTED = 'text/vnd.skriv.encrypted';
	const FILE_TYPE_SKRIV = 'text/vnd.skriv';

	const CONTEXT_DOCUMENTS = 'documents';
	const CONTEXT_USER = 'user';
	const CONTEXT_TRANSACTION = 'transaction';
	const CONTEXT_CONFIG = 'config';
	const CONTEXT_WEB = 'web';
	const CONTEXT_SKELETON = 'skel';
	const CONTEXT_FILE = 'file';

	const THUMB_CACHE_ID = 'file.thumb.%d.%d';

	public function __construct()
	{
		parent::__construct();
		$this->created = new \DateTime;
		$this->modified = new \DateTime;
	}

	public function selfCheck(): void
	{
		parent::selfCheck();
		$this->assert($this->image === 0 || $this->image === 1);
	}

	public function getContexts(): array
	{
		return [
			self::CONTEXT_DOCUMENTS,
			self::CONTEXT_USER,
			self::CONTEXT_TRANSACTION,
			self::CONTEXT_CONFIG,
			self::CONTEXT_WEB,
			self::CONTEXT_SKELETON,
			self::CONTEXT_FILE,
		];
	}

	public function delete(): bool
	{
		Files::callStorage('checkLock');
		Files::callStorage('delete', $this);

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

			if ($this->type == self::FILE_TYPE_HTML) {
				$content = strip_tags($content);
			}

			if ($this->type == self::FILE_TYPE_ENCRYPTED) {
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

		Files::callStorage('checkLock');

		if (!Files::callStorage('store', $this, $source_path, $source_content)) {
			throw new UserException('Le fichier n\'a pas pu être enregistré.');
		}

		return $this;
	}

	static protected function createAndStore(string $name, string $context, ?string $context_ref, string $source_path = null, string $source_content = null): self
	{
		$file = self::create($name, $context, $context_ref, $source_path, $source_content);

		$file->store($source_path, $source_content);
		$file->save();

		return $file;
	}

	static protected function create(string $name, string $context, ?string $context_ref, string $source_path = null, string $source_content = null): self
	{
		if (isset($source_path, $source_content)) {
			throw new \InvalidArgumentException('Either source path or source content should be set but not both');
		}

		$finfo = \finfo_open(\FILEINFO_MIME_TYPE);
		$file = new self;
		$file->set('name', $name);
		$file->set('context', $context);
		$file->set('context_ref', $context_ref);

		$db = DB::getInstance();

		if ($source_path && !$source_content) {
			$file->set('type', finfo_file($finfo, $source_path));
		}
		else {
			$file->set('type', finfo_buffer($finfo, $source_content));
		}

		$file->set('image', preg_match('/^image\/(?:png|jpe?g|gif)$/', $file->type));

		return $file;
	}

	/**
	 * Upload de fichier à partir d'une chaîne en base64
	 * @param  string $name
	 * @param  string $content
	 * @return File
	 */
	static public function createFromBase64(string $name, string $context, ?string $context_ref, string $encoded_content): self
	{
		$content = base64_decode($encoded_content);
		return self::createAndStore($name, $context, $context_ref, null, $content);
	}

	public function storeFromBase64(string $encoded_content): self
	{
		$content = base64_decode($encoded_content);
		return $this->store(null, $content);
	}

	/**
	 * Upload du fichier par POST
	 */
	static public function upload(string $key, string $context, ?string $context_ref): self
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

		return self::createAndStore($name, $context, $context_ref, $file['tmp_name']);
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

	public function listLinked(): array
	{
		return EM::getInstance(self::class)->all('SELECT * FROM files WHERE context = ? AND context_ref = ? ORDER BY name;',
			$this->context, $this->id());
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
		if ($this->isPublic()) {
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

	public function render(array $options = [])
	{
		$type = $this->type;
		if ($type == self::FILE_TYPE_HTML) {
			return \Garradin\Web\Render\HTML::render($this, null, $options);
		}
		elseif ($type == self::FILE_TYPE_SKRIV) {
			return \Garradin\Web\Render\Skriv::render($this, null, $options);
		}
		elseif ($type == self::FILE_TYPE_ENCRYPTED) {
			return \Garradin\Web\Render\EncryptedSkriv::render($this, null, $options);
		}

		throw new \LogicException('Unknown render type: ' . $type);
	}

	public function checkReadAccess(Session $session): bool
	{
		$context = $this->context;
		$ref = $this->context_ref;

		// If it's linked to a file, then we want to know what the parent file is linked to
		if ($context == self::CONTEXT_FILE) {
			return $this->parent()->checkReadAccess($session);
		}
		// Web pages and config files are always public
		else if ($context == self::CONTEXT_WEB || $context == self::CONTEXT_CONFIG) {
			return true;
		}
		else if ($context == self::CONTEXT_TRANSACTION && $session->canAccess(Session::SECTION_ACCOUNTING, Membres::DROIT_ACCES)) {
			return true;
		}
		// The user can access his own profile files
		else if ($context == self::CONTEXT_USER && $ref == $session->getUser()->id) {
			return true;
		}
		// Only users able to manage users can see their profile files
		else if ($context == self::CONTEXT_USER && $session->canAccess(Session::SECTION_USERS, Membres::DROIT_ECRITURE)) {
			return true;
		}
		// Only users with right to access documents can read documents
		else if ($context == self::CONTEXT_DOCUMENTS && $session->canAccess(Session::SECTION_DOCUMENTS, Membres::DROIT_ACCES)) {
			return true;
		}

		return false;
	}

	public function getPathForContext(string $context, $value): string
	{
		return rtrim($context . '/' . $value, '/');
	}

	public function path(): string
	{
		return self::getPathForContext($this->context, $this->context_ref) . '/' . $this->name;
	}

	/**
	 * Create a file in DB from an existing file in the local filesysteme
	 */
	static public function createFromExisting(string $path, string $root): File
	{
		$ctx = self::getContextFromPath($path);
		$fullpath = $root . '/' . $path;

		$file = File::create($name, $ctx[0], $ctx[1], $fullpath);

		$file->set('hash', sha1_file($fullpath));
		$file->set('size', filesize($fullpath));
		$file->set('modified', filemtime($fullpath));
		$file->set('created', filemtime($fullpath));

		$file->save();

		return $file;
	}

	static public function getContextFromPath(string $path): array
	{
		$context = strtok($this->path, '/');
		$value = strtok('');

		return [$context, $value];
	}

	public function parent(): ?File
	{
		if (null === $this->_parent && $this->context == self::CONTEXT_FILE) {
			$this->_parent = Files::get((int) $this->context_ref);
		}

		return $this->_parent;
	}

	public function isPublic(): bool
	{
		if ($this->context == self::CONTEXT_FILE) {
			$context = $this->parent()->context;
		}
		else {
			$context = $this->context;
		}

		if ($context == self::CONTEXT_CONFIG || $context == self::CONTEXT_WEB) {
			return true;
		}

		return false;
	}
}
