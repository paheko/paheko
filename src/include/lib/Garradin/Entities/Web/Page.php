<?php

namespace Garradin\Entities\Web;

use Garradin\Entity;
use Garradin\UserException;

use const Garradin\WWW_URL;

use KD2\DB\EntityManager as EM;

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

	public function url(): string
	{
		$url = WWW_URL . $this->uri;

		if ($this->type == self::TYPE_CATEGORY) {
			$url .= '/';
		}

		return $url;
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
		$this->assert(!$this->_file, 'Fichier manquant');
	}

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		$file = $this->file();

		if (isset($source['content']) && sha1($source['content']) != $file->hash) {
			$file->store(null, $content);
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
}
