<?php

namespace Garradin\Entities\Accounting;

use Garradin\Entity;

/**
 * Analytical projects
 */
class Project extends Entity
{
	const TABLE = 'acc_projects';

	protected ?int $id;
	protected ?string $code;
	protected string $label;
	protected bool $archived = false;
	protected $_position = [];

	public function selfCheck(): void
	{
		$db = DB::getInstance();

		if (null !== $this->code) {
			$this->assert(trim($this->code) !== '', 'Le numéro de projet est invalide.');
			$this->assert(strlen($this->code) <= 100, 'Le numéro de projet est trop long.');
			$this->assert(preg_match('/^[a-z0-9_]+$/i', $this->code), 'Le numéro de projet ne peut comporter que des lettres et des chiffres.');
		}

		$this->assert(trim($this->label) !== '', 'L\'intitulé de projet ne peut rester vide.');
		$this->assert(strlen($this->label) <= 200, 'L\'intitulé de compte ne peut faire plus de 200 caractères.');

		parent::selfCheck();
	}
}
