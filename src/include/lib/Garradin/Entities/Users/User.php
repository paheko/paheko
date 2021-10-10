<?php

namespace Garradin\Entities\Users;

use KD2\DB\EntityManager;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Config;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\ValidationException;

use Garradin\Users\DynamicFields;

use KD2\SMTP;

class User extends Entity
{
	const TABLE = 'users';

	protected function __construct()
	{
		$fields = DynamicFields::all();

		foreach ($fields as $key => $config) {
			$this->$key = null;
			$this->_types[$key] = $config->type;
		}

		parent::__construct();
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
}
