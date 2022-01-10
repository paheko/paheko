<?php

namespace Garradin\Entities\Users;

use KD2\DB\EntityManager;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Config;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\ValidationException;

use Garradin\Users\Categories;
use Garradin\Users\DynamicFields;

use Garradin\Entities\Files\File;

use KD2\SMTP;

#[AllowDynamicProperties]
class User extends Entity
{
	const TABLE = 'users';

	protected bool $_construct = false;

	public function __construct()
	{
		$this->_construct = true;

		foreach (DynamicField::SYSTEM_FIELDS as $key => $type) {
			$this->_types[$key] = $type;
			$this->$key = null;
		}

		$fields = DynamicFields::getInstance()->all();

		foreach ($fields as $key => $config) {
			$this->_types[$key] = DynamicField::PHP_TYPES[$config->type];
			$this->$key = null;
		}

		$this->_construct = false;

		parent::__construct();
	}

	public function set(string $key, $value, bool $loose = false, bool $check_for_changes = true) {
		if ($this->_construct && $value === null) {
			$this->$key = $value;
			return;
		}

		return parent::set($key, $value, $loose, $check_for_changes);
	}

	public function selfCheck(): void
	{
		$df = DynamicFields::getInstance();

		// Check email addresses
		foreach (DynamicFields::getEmailFields() as $field) {
			$this->assert($this->$field === null || SMTP::checkEmailIsValid($this->$field, false), 'Cette adresse email n\'est pas valide.');
		}

		// check user number
		$field = DynamicFields::getNumberField();
		$this->assert($this->$field !== null && ctype_alnum($this->$field), 'Numéro de membre invalide : ne peut contenir que des chiffres et des lettres.');

		$db = DB::getInstance();
		$this->assert(!$this->exists() && !$db->test(self::TABLE, sprintf('%s = ?', $db->quoteIdentifier($field)), $this->$field), 'Ce numéro de membre est déjà utilisé par un autre membre.');
		$this->assert($this->exists() && !$db->test(self::TABLE, sprintf('%s = ? AND id != ?', $db->quoteIdentifier($field)), $this->$field, $this->id()), 'Ce numéro de membre est déjà utilisé par un autre membre.');

		$field = DynamicFields::getLoginField();
		if ($this->$field !== null) {
			$this->assert(!$this->exists() && !$db->test(self::TABLE, sprintf('%s = ? COLLATE NOCASE ', $db->quoteIdentifier($field)), $this->$field), sprintf('Le champ %s utilisé comme identifiant est déjà utilisé par un autre membre. Il doit être unique pour chaque membre.', $field_name));
			$this->assert($this->exists() && !$db->test(self::TABLE, sprintf('%s = ? COLLATE NOCASE AND id != ?', $db->quoteIdentifier($field)), $this->$field, $this->id()), sprintf('Le champ %s utilisé comme identifiant est déjà utilisé par un autre membre. Il doit être unique pour chaque membre.', $field_name));
		}
	}

	public function delete(): bool
	{
		$session = Session::getInstance();

		if ($session->isLogged()) {
			$user = $session->getUser();

			if ($user->id == $this->id) {
				throw new UserException('Il n\'est pas possible de supprimer son propre compte.');
			}
		}

		return parent::delete();
	}

	public function category(): Category
	{
		return Categories::get($this->id_category);
	}

	public function attachementsDirectory(): string
	{
		return File::CONTEXT_USER . '/' . $this->id();
	}

	public function name(): string
	{
		$out = [];

		foreach (DynamicFields::getNameFields() as $key) {
			$out[] = $this->$key;
		}

		return implode(' ', $out);
	}
}
