<?php

namespace Garradin\Entities\Web;

use Garradin\Entity;
use Garradin\UserException;

use KD2\DB\EntityManager;

class Page extends Entity
{
	protected $id;
	protected $category_id;
	protected $title;
	protected $draft;
	protected $modified;

	protected $_types = [
		'id'          => 'int',
		'category_id' => 'int',
		'title'       => 'string',
		'draft'       => 'int',
		'modified'    => 'DateTime',
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
