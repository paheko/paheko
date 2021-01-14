<?php

namespace Garradin\Entities\Documents;

use Garradin\Entity;
use Garradin\ValidationException;

class Template extends Entity
{
	const TABLE = 'docs_templates';

	protected $id;
	protected $related_type;
	protected $related_to;
	protected $label;
	protected $description;
	protected $content;
	protected $created;
	protected $modified;

	protected $_types = [
		'id'           => 'int',
		'related_type' => 'string',
		'related_to'   => '?string',
		'label'        => 'string',
		'description'  => '?string',
		'content'      => 'string',
		'created'      => 'DateTime',
		'modified'     => 'DateTime',
	];

	const TYPE_USERS = 'users';
	const TYPE_ACCOUNTING = 'accounting';

	public function selfCheck(): void
	{
		parent::selfCheck();

		static $related_types = [self::USERS, self::ACCOUNTING];
		$this->assert(in_array($this->related, $related_types), 'Type inconnu');
	}
}
