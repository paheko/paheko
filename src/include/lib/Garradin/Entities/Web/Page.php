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
	protected $name;
	protected $title;
	protected $type;
	protected $status;
	protected $format;
	protected $published;
	protected $modified;
	protected $content;

	protected $_types = [
		'id'        => 'int',
		'parent'    => '?string',
		'path'       => 'string',
		'name'      => 'string',
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
		$page->name = 'index.txt';;
		$page->modified = new \DateTime;

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

		if ($this->file()->modified != $this->modified) {
			// FIXME reload
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
		if ($this->format == self::FORMAT_SKRIV) {
			return \Garradin\Web\Render\Skriv::render($this->file(), $this->content, $options);
		}
		else if ($this->format == self::FORMAT_ENCRYPTED) {
			return \Garradin\Web\Render\EncryptedSkriv::render($this->file(), $this->content, $options);
		}
	}

	public function preview(string $content): string
	{
		return Skriv::render($this->file(), $content, ['prefix' => '#']);
	}

	public function filepath(): string
	{
		$parts = [
			File::CONTEXT_WEB,
			$this->path,
			$this->name,
		];

		$parts = array_filter($parts);

		return implode('/', $parts);
	}

	public function path(): string
	{
		return $this->path;
	}

	public function save(): bool
	{
		$file = $this->file();

		$exists = $this->exists();
		$realpath = $this->filepath();

		if (!$exists && !$file) {
			$this->_file = File::createAndStore(dirname($realpath), basename($realpath), null, $this->export());
		}

		parent::save();

		if ($exists && (isset($this->_modified['parent']) || isset($this->_modified['name']) || isset($this->_modified['path']))) {
			// Rename parent directory
			$dir = Files::get($file->path);
			$dir->rename(dirname($realpath));

			$file = $this->file(true);
		}

		$file->setContent($this->export());

		return true;
	}

	public function delete(): bool
	{
		$this->file()->delete();
		return parent::delete();
	}

	public function selfCheck(): void
	{
		$this->assert($this->type === self::TYPE_CATEGORY || $this->type === self::TYPE_PAGE, 'Unknown page type');
		$this->assert(array_key_exists($this->status, self::STATUS_LIST), 'Unknown page status');
		$this->assert(array_key_exists($this->format, self::FORMATS_LIST), 'Unknown page format');
		$this->assert(trim($this->title) !== '', 'Le titre ne peut rester vide');
		$this->assert(trim($this->path) !== '', 'Le chemin ne peut rester vide');
		$this->assert((bool) $this->file(), 'Fichier manquant');

		$db = DB::getInstance();
		$where = $this->exists() ? sprintf(' AND id != %d', $this->id()) : '';
		$this->assert(!$db->test(self::TABLE, 'path = ?' . $where, $this->path), 'Cette adresse URI est déjà utilisée par une autre page, merci d\'en choisir une autre');
	}

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['date']) && isset($source['date_time'])) {
			$source['created'] = $source['date'] . ' ' . $source['date_time'];
		}

		if (isset($source['title']) && is_null($this->path)) {
			$source['path'] = trim($this->parent . '/' . Utils::transformTitleToURI($source['title']), '/');
		}

		if (isset($source['uri'])) {
			$source['path'] = trim($this->parent . '/' . Utils::transformTitleToURI($source['uri']), '/');
		}

		if (!empty($source['encrypted']) ) {
			$this->format = self::FORMAT_ENCRYPTED;
		}
		else {
			$this->format = self::FORMAT_SKRIV;
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
				return $a->name != $this->name && $a->type != $a::TYPE_DIRECTORY;
			});

			$this->_attachments = $list;
		}

		return $this->_attachments;
	}

	static public function findTaggedAttachments(string $text): array
	{
		preg_match_all('/<<?(?:fichier|image)\s*(?:\|\s*)?([\w\d_.-]+)/ui', $text, $match, PREG_PATTERN_ORDER);
		preg_match_all('/(?:fichier|image):\/\/([\w\d_.-]+)/ui', $text, $match2, PREG_PATTERN_ORDER);

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

	static public function importFromRaw(string $str): void
	{
		$str = preg_replace("/\r\n|\r|\n/", "\n", $str);
		$str = explode("\n\n----\n\n", $str, 2);

		if (count($str) !== 2) {
			// FIXME: handle this case with more subtlety
			throw new \LogicException('Invalid page');
		}

		list($meta, $content) = $str;

		$meta = explode("\n", trim($meta));

		foreach ($meta as $line) {
			$key = strtolower(trim(strtok($line, ':')));
			$value = trim(strtok(''));

			if ($key == 'title') {
				$this->title = $value;
			}
			elseif ($key == 'published') {
				$this->published = new \DateTime($value);
			}
			elseif ($key == 'format') {
				$value = strtolower($value);

				if (!array_key_exists($value, self::FORMATS_LIST)) {
					throw new \LogicException('Unknown format: ' . $value);
				}

				$this->format = $value;
			}
			elseif ($key == 'status') {
				$value = strtolower($value);

				if (!array_key_exists($value, self::STATUS_LIST)) {
					throw new \LogicException('Unknown status: ' . $value);
				}

				$this->status = $value;
			}
			else {
				// Ignore other metadata
			}
		}

		$this->content = trim($content, "\n\r");
	}

	static public function fromFile(File $file, array $files, string $str, ?int $parent_id = null): self
	{
		$page = new self;

		// Path is relative to web root
		$page->parent = substr(dirname($file->path()), strlen(File::CONTEXT_WEB . '/'));

		$page->importFromRaw($str);
		$page->type = self::TYPE_PAGE; // FIXME
		$page->modified = $file->modified;

		foreach ($files as $subfile) {
			if ($subfile->type == File::TYPE_DIRECTORY) {
				$page->type = self::TYPE_CATEGORY;
			}
		}

		return $page;
	}
}
