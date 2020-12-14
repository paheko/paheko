<?php

namespace Garradin\Entities\Web;

use Garradin\Entity;
use Garradin\UserException;
use Garradin\Entities\Files\File;

use KD2\DB\EntityManager as EM;

use const Garradin\WWW_URL;

class Page extends Entity
{
	const TABLE = 'web_pages';

	protected $id;
	protected $parent_id;
	protected $type;
	protected $status;
	protected $title;
	protected $uri;
	protected $modified;

	protected $_types = [
		'id'        => 'int',
		'parent_id' => '?int',
		'type'      => 'int',
		'status'    => 'int',
		'uri'       => 'string',
		'title'     => 'string',
		'modified'  => 'DateTime',
	];

	protected $_file;

	const STATUS_ONLINE = 1;
	const STATUS_DRAFT = 0;

	const TYPE_CATEGORY = 1;
	const TYPE_PAGE = 2;

	const FILE_TYPE_HTML = 'text/html';
	const FILE_TYPE_ENCRYPTED = 'text/vnd.skriv.encrypted';
	const FILE_TYPE_SKRIV = 'text/vnd.skriv';

	public function url(): string
	{
		$url = WWW_URL . $this->uri;

		if ($this->type == self::TYPE_CATEGORY) {
			$url .= '/';
		}

		return $url;
	}

	public function raw(): string
	{
		return $this->file()->fetch();
	}

	public function file(): File
	{
		if (null === $this->_file) {
			$this->_file = EM::findOneById(File::class, $this->id);
		}

		return $this->_file;
	}

	public function setFile(File $file)
	{
		$this->_file = $file;
	}

	public function save(): bool
	{
		$this->modified = new \DateTime;

		$file = $this->file();
		$file->save();

		$this->id($file->id());

		return parent::save();
	}

	public function selfCheck(): void
	{
		$this->assert($this->type === self::TYPE_CATEGORY || $this->type === self::TYPE_PAGE, 'Unknown page type');
		$this->assert($this->status === self::STATUS_DRAFT || $this->status === self::STATUS_ONLINE, 'Unknown page status');
		$this->assert(trim($this->title) !== '', 'Le titre ne peut rester vide');
		$this->assert(trim($this->uri) !== '', 'L\'URI ne peut rester vide');
		$this->assert((bool) $this->_file, 'Fichier manquant');
	}

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['parent_id']) && is_array($source['parent_id'])) {
			$source['parent_id'] = key($source['parent_id']);
		}

		$file = $this->file();

		if (isset($source['content']) && sha1($source['content']) != $file->hash) {
			$file->store(null, $source['content']);
		}

		if (isset($source['date']) && isset($source['date_time'])) {
			$file->importForm(['created' => sprintf('%s %s', $source['date'], $source['date_time'])]);
		}

		if (!empty($source['encrypted']) ) {
			$file->set('type', self::FILE_TYPE_ENCRYPTED);
		}
		else {
			$file->set('type', self::FILE_TYPE_SKRIV);
		}

		return $this->import($source);
	}

	static public function create(int $type, string $title, string $content, int $status = self::STATUS_ONLINE): Page
	{
		static $types = [self::TYPE_PAGE, self::TYPE_CATEGORY];

		if (!in_array($type, $types)) {
			throw new \InvalidArgumentException('Unknown type');
		}

		$page = new self;
		$page->type = $type;
		$page->title = $title;
		$page->uri = Utils::transformTitleToURI($title);
		$page->status = $status;

		$file = new File;
		$file->type = 'text/html';
		$file->image = 0;

		if ($type == self::TYPE_PAGE) {
			$file->name = $page->uri . '.html';
		}
		else {
			$file->name = 'index.html';
		}

		$file->store(null, $content);
		$page->save();

		return $page;
	}

	public function render(): string
	{
		static $render_types = [self::FILE_TYPE_SKRIV, self::FILE_TYPE_ENCRYPTED, self::FILE_TYPE_HTML];

		if (!in_array($this->type, $render_types)) {
			throw new \LogicException('Render can not be called on files of type: ' . $this->type);
		}

		$content = $this->fetch();

		if ($this->type == self::FILE_TYPE_HTML) {
			return \Garradin\Files\Render\HTML::render($this, $content);
		}
		elseif ($this->type == self::FILE_TYPE_SKRIV) {
			return \Garradin\Files\Render\Skriv::render($this, $content);
		}
		elseif ($this->type == self::FILE_TYPE_ENCRYPTED) {
			return \Garradin\Files\Render\EncryptedSkriv::render($this, $content);
		}

		throw new \LogicException('Unknown render type');
	}
}
