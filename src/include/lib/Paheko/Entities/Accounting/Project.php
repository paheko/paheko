<?php

namespace Paheko\Entities\Accounting;

use Paheko\DB;
use Paheko\Entity;

/**
 * Analytical projects
 */
class Project extends Entity
{
	const NAME = 'Projet analytique';
	const TABLE = 'acc_projects';

	protected ?int $id;
	protected ?string $code;
	protected string $label;
	protected ?string $description;
	protected bool $archived = false;
	protected $_position = [];

	public function selfCheck(): void
	{
		if (null !== $this->code) {
			$this->assert(trim($this->code) !== '', 'Le numéro de projet est invalide.');
			$this->assert(strlen($this->code) <= 100, 'Le numéro de projet est trop long.');
			$this->assert(preg_match('/^[A-Z0-9_]+$/', $this->code), 'Le numéro de projet ne peut comporter que des lettres majuscules et des chiffres.');

			$db = DB::getInstance();

			if ($this->exists()) {
				$this->assert(!$db->test(self::TABLE, 'code = ? AND id != ?', $this->code, $this->id()), 'Ce code est déjà utilisé par un autre projet.');
			}
			else {
				$this->assert(!$db->test(self::TABLE, 'code = ?', $this->code), 'Ce code est déjà utilisé par un autre projet.');
			}
		}

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

		$source['archived'] = !empty($source['archived']);

		parent::importForm($source);
	}
}
