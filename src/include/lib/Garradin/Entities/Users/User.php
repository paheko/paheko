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
use Garradin\Users\Session;

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

		foreach ($df->all() as $field) {
			if ($field->required) {
				$this->assert(null !== $this->{$field->name}, sprintf('"%s" : ce champ est requis', $field->label));
			}
		}

		// Check email addresses
		foreach (DynamicFields::getEmailFields() as $field) {
			$this->assert($this->$field === null || SMTP::checkEmailIsValid($this->$field, false), 'Cette adresse email n\'est pas valide.');
		}

		// check user number
		$field = DynamicFields::getNumberField();
		$this->assert($this->$field !== null && ctype_alnum($this->$field), 'Numéro de membre invalide : ne peut contenir que des chiffres et des lettres.');

		$db = DB::getInstance();

		if (!$this->exists()) {
			$number_exists = $db->test(self::TABLE, sprintf('%s = ?', $db->quoteIdentifier($field)), $this->$field);
		}
		else {
			$number_exists = $db->test(self::TABLE, sprintf('%s = ? AND id != ?', $db->quoteIdentifier($field)), $this->$field, $this->id());
		}

		$this->assert(!$number_exists, 'Ce numéro de membre est déjà attribué à un autre membre.');

		$field = DynamicFields::getLoginField();
		if ($this->$field !== null) {
			if (!$this->exists()) {
				$login_exists = $db->test(self::TABLE, sprintf('%s = ? COLLATE NOCASE', $db->quoteIdentifier($field)), $this->$field);
			}
			else {
				$login_exists = $db->test(self::TABLE, sprintf('%s = ? COLLATE NOCASE AND id != ?', $db->quoteIdentifier($field)), $this->$field, $this->id());
			}

			$this->assert(!$login_exists, sprintf('Le champ "%s" (utilisé comme identifiant de connexion) est déjà utilisé par un autre membre. Il doit être unique pour chaque membre.', $df->fieldByKey($field)->label));
		}
	}

	public function delete(): bool
	{
		$session = Session::getInstance();

		if ($session->isLogged()) {
			$user = $session->getUser();

			if ($user->id == $this->id) {
				throw new UserException('Il n\'est pas possible de supprimer son propre compte. Merci de demander à un administrateur de le faire.');
			}
		}

		Files::delete($this->attachementsDirectory());

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

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['password'])) {
			$this->assert($source['password'] == ($source['password_confirmed'] ?? null), 'La confirmation de mot de passe doit être identique au mot de passe.');
		}

		return parent::importForm($source);
	}
}
