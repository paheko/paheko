<?php

namespace Garradin\Entities\Web;

use Garradin\Entity;
use Garradin\UserException;

use KD2\DB\EntityManager;

class Page extends Entity
{
	protected $id;
	protected $parent_id;
	protected $status;
	protected $title;
	protected $draft;
	protected $modified;

	protected $_types = [
		'id'        => 'int',
		'parent_id' => 'int',
		'status'    => 'int',
		'title'     => 'string',
		'draft'     => 'int',
		'modified'  => 'DateTime',
	];

	protected $_file;

	const STATUS_ONLINE = 1;
	const STATUS_DRAFT = 0;

	public function file(): File
	{
		if (null === $this->_file) {
			$this->_file = EM::findOneById(File::class, $this->id);
		}

		return $this->_file;
	}

	public function save()
	{
		$file = $this->file();
		$file->save();

		$this->id($file->id());

		parent::save();
	}
}
