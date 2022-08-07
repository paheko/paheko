<?php
declare(strict_types=1);

namespace Garradin\Entities\Users;

use KD2\DB\EntityManager;

use Garradin\DB;
use Garradin\Config;
use Garradin\Entity;
use Garradin\Form;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\ValidationException;

use Garradin\Files\Files;

use Garradin\Users\Categories;
use Garradin\Users\Emails;
use Garradin\Users\DynamicFields;
use Garradin\Users\Session;
use Garradin\Users\Users;

use Garradin\Entities\Files\File;

use KD2\SMTP;
use KD2\DB\EntityManager as EM;

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

		// Don't bother for type with generated columns
		if ($this->_types[$key] == 'dynamic') {
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
		$this->assert($this->$field !== null && is_numeric($this->$field), 'Numéro de membre invalide : ne peut contenir que des chiffres');

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

		if ($this->id_parent !== null) {
			$this->assert(!$this->is_parent, 'Un membre ne peut être responsable et rattaché en même temps.');
			$this->assert($this->id_parent > 0, 'Invalid parent ID');
			$this->assert(!$this->exists() || $this->id_parent != $this->id(), 'Invalid parent ID');
			$this->assert(!$db->test(self::TABLE, 'id = ? AND id_parent IS NOT NULL', $this->id_parent), 'Le membre sélectionné comme responsable est déjà rattaché à un autre membre.');
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

	public function save(bool $selfcheck = true): bool
	{
		$columns = array_intersect(DynamicFields::getInstance()->getSearchColumns(), array_keys($this->_modified));
		parent::save($selfcheck);

		// We are not using a trigger as it would make modifying the users table from outside impossible
		// (because the transliterate_to_ascii function does not exist)
		if (count($columns)) {
			DynamicFields::getInstance()->rebuildUserSearchCache($this->id());
		}

		return true;
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

		if (isset($source['id_parent']) && is_array($source['id_parent'])) {
			$source['id_parent'] = Form::getSelectorValue($source['id_parent']);
		}

		return parent::importForm($source);
	}

	public function canEmail(): bool
	{
		foreach (DynamicFields::getEmailFields() as $f) {
			if (!empty($this->$f)) {
				return true;
			}
		}

		return false;
	}

	public function getNameAndEmail(): string
	{
		$email_field = DynamicFields::getFirstEmailField();

		return sprintf('"%s" <%s>', $this->name(), $this->{$email_field});
	}

	public function isChild(): bool
	{
		return (bool) $this->id_parent;
	}

	public function getParentName(): ?string
	{
		if (!$this->isChild()) {
			return null;
		}

		return Users::getName($this->id_parent);
	}

	public function getParentSelector(): ?array
	{
		if (!$this->isChild()) {
			return null;
		}

		return [$this->id_parent => $this->getParentName()];
	}

	public function hasChildren(): bool
	{
		return DB::getInstance()->test(self::TABLE, 'id_parent = ?', $this->id());
	}

	public function listChildren(): array
	{
		$name = DynamicFields::getNameFieldsSQL();
		return DB::getInstance()->getGrouped(sprintf('SELECT id, %s AS name FROM %s WHERE id_parent = ?;', $name, self::TABLE), $this->id());
	}

	public function listSiblings(): array
	{
		if (!$this->id_parent) {
			return [];
		}

		$name = DynamicFields::getNameFieldsSQL();
		return DB::getInstance()->getGrouped(sprintf('SELECT id, %s AS name FROM %s WHERE id_parent = ? AND id != ?;', $name, self::TABLE), $this->id_parent, $this->id());
	}

	public function sendMessage(string $subject, string $message, bool $send_copy, ?User $from = null)
	{
		$config = Config::getInstance();
		$email_field = DynamicFields::getFirstEmailField();

		$from = $from ? $from->getNameAndEmail() : null;

		Emails::queue(Emails::CONTEXT_PRIVATE, [$this->{$email_field} => null], $from, $subject, $message);

		if ($send_copy) {
			Emails::queue(Emails::CONTEXT_PRIVATE, [$config->org_email => null], null, $subject, $message);
		}
	}
}
