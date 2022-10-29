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
	protected string $code;
	protected string $label;
	protected ?string $description;
	protected bool $archived = false;
	protected $_position = [];

	public function selfCheck(): void
	{
		$db = DB::getInstance();

		$this->assert(trim($this->code) !== '', 'Le numéro de projet est invalide.');
		$this->assert(strlen($this->code) <= 100, 'Le numéro de projet est trop long.');
		$this->assert(preg_match('/^[A-Z0-9_]+$/', $this->code), 'Le numéro de projet ne peut comporter que des lettres majuscules et des chiffres.');

		$this->assert(trim($this->label) !== '', 'L\'intitulé de projet ne peut rester vide.');
		$this->assert(strlen($this->label) <= 200, 'L\'intitulé de compte ne peut faire plus de 200 caractères.');

		if (null !== $this->description) {
			$this->assert(trim($this->description) !== '', 'L\'intitulé de projet est invalide.');
			$this->assert(strlen($this->description) <= 2000, 'L\'intitulé de compte ne peut faire plus de 2000 caractères.');
		}

		parent::selfCheck();
	}

	public function importForm(?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (!empty($source['code'])) {
			$source['code'] = strtoupper($source['code']);
		}

		parent::importForm($data);
	}
}
