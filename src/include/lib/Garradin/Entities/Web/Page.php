<?php

namespace Garradin\Entities\Web;

use Garradin\DB;
use Garradin\Entity;
use Garradin\UserException;
use Garradin\Utils;
use Garradin\Entities\Files\File;
use Garradin\Files\Files;
use Garradin\Web\Render\Skriv;

use KD2\DB\EntityManager as EM;

use const Garradin\WWW_URL;

class Page extends Entity
{
	const TABLE = 'web_pages';

	protected $id;
	protected $parent;
	protected $path;
	protected $_name = 'index.txt';
	protected $file_path;
	protected $title;
	protected $type;
	protected $status;
	protected $format;
	protected $published;
	protected $modified;
	protected $content;

	protected $_types = [
		'id'        => 'int',
		'parent'    => 'string',
		'path'      => 'string',
		'file_path' => 'string',
		'title'     => 'string',
		'type'      => 'int',
		'status'    => 'string',
		'format'    => 'string',
		'published' => 'DateTime',
		'modified'  => 'DateTime',
		'content'   => 'string',
	];

	const FORMAT_SKRIV = 'skriv';
	const FORMAT_ENCRYPTED = 'skriv/encrypted';

	const FORMATS_LIST = [
		self::FORMAT_SKRIV => 'SkrivML',
		self::FORMAT_ENCRYPTED => 'Chiffré',
	];

	const STATUS_ONLINE = 'online';
	const STATUS_DRAFT = 'draft';

	const STATUS_LIST = [
		self::STATUS_ONLINE => 'En ligne',
		self::STATUS_DRAFT => 'Brouillon',
	];

	const TYPE_CATEGORY = 1;
	const TYPE_PAGE = 2;

	const TEMPLATES = [
		self::TYPE_PAGE => 'article.html',
		self::TYPE_CATEGORY => 'category.html',
	];

	protected $_file;
	protected $_attachments;

	static public function create(int $type, ?string $parent, string $title, string $status = self::STATUS_ONLINE): self
	{
		$page = new self;
		$data = compact('type', 'parent', 'title', 'status');
		$data['content'] = '';

		$page->importForm($data);
		$page->published = new \DateTime;
		$page->modified = new \DateTime;
		$page->file_path = $page->filepath();
		$page->type = $type;

		return $page;
	}

	public function file(bool $force_reload = false)
	{
		if (null === $this->_file || $force_reload) {
			$this->_file = Files::get($this->filepath());
		}

		return $this->_file;
	}

	public function load(array $data): void
	{
		parent::load($data);

		if ($this->file() && $this->file()->modified > $this->modified) {
			$this->loadFromFile($this->file());
			$this->save();
		}
	}

	public function url(): string
	{
		return WWW_URL . $this->path();
	}

	public function uri(): string
	{
		return basename($this->path);
	}

	public function template(): string
	{
		return self::TEMPLATES[$this->type];
	}

	public function asTemplateArray(): array
	{
		$out = $this->asArray();
		$out['url'] = $this->url();
		$out['html'] = $this->render();
		$out['uri'] = $this->uri();
		return $out;
	}

	public function render(array $options = []): string
	{
		if (!$this->file()) {
			throw new \LogicException('File does not exist: '  . $this->file_path);
		}
		if ($this->format == self::FORMAT_SKRIV) {
			return \Garradin\Web\Render\Skriv::render($this->file(), $this->content, $options);
		}
		else if ($this->format == self::FORMAT_ENCRYPTED) {
			return \Garradin\Web\Render\EncryptedSkriv::render($this->file(), $this->content);
		}

		throw new \LogicException('Invalid format: ' . $this->format);
	}

	public function preview(string $content): string
	{
		return Skriv::render($this->file(), $content, ['prefix' => '#']);
	}

	public function filepath(bool $stored = true): string
	{
		return $stored && isset($this->file_path) ? $this->file_path : File::CONTEXT_WEB . '/' . $this->path . '/' . $this->_name;
	}

	public function path(): string
	{
		return $this->path;
	}

	public function syncFileContent(): void
	{
		$export = $this->export();

		if (!$this->file()->exists() || $this->file()->fetch() !== $export) {
			$this->file()->store(null, $this->export());
		}

		$this->syncSearch();
	}

	public function syncSearch(): void
	{
		$content = $this->format == self::FORMAT_ENCRYPTED ? null : strip_tags($this->render());
		$this->file()->indexForSearch(null, $content, $this->title);
	}

	public function save(): bool
	{
		$exists = $this->exists();
		$file = $exists ? $this->file() : null;

		// Reset filepath
		$source = $this->filepath();

		// Move file if required
		$this->set('file_path', $this->filepath(false));
		$target = $this->filepath();

		$edit_file = false;

		if (!$exists && !$file) {
			$file = $this->_file = File::create(dirname($target), basename($target), null, '');
			$file->set('mime', 'text/plain');
			$edit_file = true;
		}
		else {
			$edit_file = (bool) count(array_intersect(['title', 'status', 'published', 'format', 'content'], array_keys($this->_modified)));
		}

		if ($edit_file) {
			$this->set('modified', new \DateTime);
		}

		try {
			if ($target !== $source && $exists) {
				// Rename parent directory
				$dir = Files::get(dirname($source));
				$dir->rename(dirname($target));
			}

			parent::save();
		}
		catch (\Exception $e) {
			// Cancel rename
			if ($target !== $source && $exists) {
				$dir = Files::get(dirname($target));
				$dir->rename(dirname($source));
			}

			throw $e;
		}

		// Reload linked file
		if ($target !== $source) {
			$file = $this->file(true);
		}

		// File content has been modified
		if ($edit_file) {
			$this->syncFileContent();
		}

		return true;
	}

	public function delete(): bool
	{
		Files::get(dirname($this->file_path))->delete();
		return parent::delete();
	}

	public function selfCheck(): void
	{
		$db = DB::getInstance();
		$this->assert($this->type === self::TYPE_CATEGORY || $this->type === self::TYPE_PAGE, 'Unknown page type');
		$this->assert(array_key_exists($this->status, self::STATUS_LIST), 'Unknown page status');
		$this->assert(array_key_exists($this->format, self::FORMATS_LIST), 'Unknown page format');
		$this->assert(trim($this->title) !== '', 'Le titre ne peut rester vide');
		$this->assert(trim($this->file_path) !== '', 'Le chemin de fichier ne peut rester vide');
		$this->assert(trim($this->path) !== '', 'Le chemin ne peut rester vide');
		$this->assert($this->path !== $this->parent, 'Invalid parent page');
		$this->assert((bool) $this->file(), 'Fichier manquant');
		$this->assert($this->parent === '' || $db->test(self::TABLE, 'path = ?', $this->parent), 'Page parent inexistante');

		$where = $this->exists() ? sprintf(' AND id != %d', $this->id()) : '';
		$this->assert(!$db->test(self::TABLE, 'path = ?' . $where, $this->path), 'Cette adresse URI est déjà utilisée par une autre page, merci d\'en choisir une autre : ' . $this->path);
	}

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['date']) && isset($source['date_time'])) {
			$source['published'] = $source['date'] . ' ' . $source['date_time'];
		}

		$parent = $this->parent;

		if (array_key_exists('parent', $source)) {
			if (is_array($source['parent'])) {
				$source['parent'] = key($source['parent']);
			}

			if (empty($source['parent'])) {
				$source['parent'] = '';
			}


			$parent = $source['parent'];
			$source['path'] = trim($parent . '/' . basename($this->path), '/');
		}

		if (isset($source['title']) && is_null($this->path)) {
			$source['path'] = trim($parent . '/' . Utils::transformTitleToURI($source['title']), '/');
		}

		if (isset($source['uri'])) {
			$source['path'] = trim($parent . '/' . Utils::transformTitleToURI($source['uri']), '/');
		}

		if (!empty($source['encryption']) ) {
			$this->set('format', self::FORMAT_ENCRYPTED);
		}
		else {
			$this->set('format', self::FORMAT_SKRIV);
		}

		return parent::importForm($source);
	}

	public function getBreadcrumbs(): array
	{
		$sql = '
			WITH RECURSIVE parents(title, parent, path, level) AS (
				SELECT title, parent, path, 1 FROM web_pages WHERE id = ?
				UNION ALL
				SELECT p.title, p.parent, p.path, level + 1
				FROM web_pages p
					JOIN parents ON parents.parent = p.path
			)
			SELECT path, title FROM parents ORDER BY level DESC;';
		return DB::getInstance()->getAssoc($sql, $this->id());
	}

	public function listAttachments(): array
	{
		if (null === $this->_attachments) {
			$list = Files::list(dirname($this->filepath()));

			// Remove the page itself
			$list = array_filter($list, function ($a) {
				return $a->name != $this->_name && $a->type != $a::TYPE_DIRECTORY;
			});

			$this->_attachments = $list;
		}

		return $this->_attachments;
	}

	static public function findTaggedAttachments(string $text): array
	{
		preg_match_all('/<<?(?:file|image)\s*(?:\|\s*)?([\w\d_.-]+)/ui', $text, $match, PREG_PATTERN_ORDER);
		preg_match_all('/#(?:file|image):\[([\w\d_.-]+)\]/ui', $text, $match2, PREG_PATTERN_ORDER);

		return array_merge($match[1], $match2[1]);
	}

	/**
	 * Return list of images
	 * If $all is FALSE then this will only return images that are not present in the content
	 */
	public function getImageGallery(bool $all = true): array
	{
		return $this->getAttachmentsGallery($all, true);
	}

	/**
	 * Return list of files
	 * If $all is FALSE then this will only return files that are not present in the content
	 */
	public function getAttachmentsGallery(bool $all = true, bool $images = false): array
	{
		$out = [];
		$tagged = [];

		if (!$all) {
			$tagged = $this->findTaggedAttachments($this->content);
		}

		foreach ($this->listAttachments() as $a) {
			if ($images && !$a->image) {
				continue;
			}
			elseif (!$images && $a->image) {
				continue;
			}

			// Skip
			if (!$all && in_array($a->name, $tagged)) {
				continue;
			}

			$out[] = $a;
		}

		return $out;
	}

	public function export(): string
	{
		$meta = [
			'Title' => str_replace("\n", '', trim($this->title)),
			'Status' => $this->status,
			'Published' => $this->published->format('Y-m-d H:i:s'),
			'Format' => $this->format,
		];

		$out = '';

		foreach ($meta as $key => $value) {
			$out .= sprintf("%s: %s\n", $key, $value);
		}

		$out .= "\n----\n\n" . $this->content;

		return $out;
	}

	public function importFromRaw(string $str): bool
	{
		$str = preg_replace("/\r\n|\r|\n/", "\n", $str);
		$str = explode("\n\n----\n\n", $str, 2);

		if (count($str) !== 2) {
			return false;
		}

		list($meta, $content) = $str;

		$meta = explode("\n", trim($meta));

		foreach ($meta as $line) {
			$key = strtolower(trim(strtok($line, ':')));
			$value = trim(strtok(''));

			if ($key == 'title') {
				$this->set('title', $value);
			}
			elseif ($key == 'published') {
				$this->set('published', new \DateTime($value));
			}
			elseif ($key == 'format') {
				$value = strtolower($value);

				if (!array_key_exists($value, self::FORMATS_LIST)) {
					throw new \LogicException('Unknown format: ' . $value);
				}

				$this->set('format', $value);
			}
			elseif ($key == 'status') {
				$value = strtolower($value);

				if (!array_key_exists($value, self::STATUS_LIST)) {
					throw new \LogicException('Unknown status: ' . $value);
				}

				$this->set('status', $value);
			}
			else {
				// Ignore other metadata
			}
		}

		$this->set('content', trim($content, "\n\r"));

		return true;
	}

	public function loadFromFile(File $file): void
	{
		if (!$this->importFromRaw($file->fetch())) {
			throw new \LogicException('Invalid page content: ' . $file->parent);
		}

		$this->set('modified', $file->modified);
		$this->set('type', self::TYPE_PAGE); // Default

		foreach (Files::list($file->parent) as $subfile) {
			if ($subfile->type == File::TYPE_DIRECTORY) {
				$this->set('type', self::TYPE_CATEGORY);
				break;
			}
		}
	}

	static public function fromFile(File $file): self
	{
		$page = new self;

		// Path is relative to web root
		$page->set('file_path', $file->path);
		$page->set('path', substr(dirname($file->path), strlen(File::CONTEXT_WEB . '/')));
		$page->set('parent', dirname($page->path) == '.' ? '' : dirname($page->path));

		$page->loadFromFile($file);
		return $page;
	}
}
