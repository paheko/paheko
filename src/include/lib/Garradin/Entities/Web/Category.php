<?php

namespace Garradin\Entities\Web;

use Garradin\Entity;
use Garradin\UserException;

use KD2\DB\EntityManager;

class Category extends Entity
{
	protected $id;
	protected $parent_id;
	protected $title;

	protected $_types = [
		'id'        => 'int',
		'parent_id' => 'int',
		'title'     => 'string',
	];

	protected $_file;

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
