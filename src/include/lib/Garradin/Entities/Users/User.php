<?php
declare(strict_types=1);

namespace Garradin\Entities\Users;

use KD2\DB\EntityManager;

use Garradin\DB;
use Garradin\Config;
use Garradin\Entity;
use Garradin\Form;
use Garradin\Log;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\ValidationException;

use Garradin\Files\Files;

use Garradin\Users\Categories;
use Garradin\Email\Emails;
use Garradin\Email\Templates as EmailTemplates;
use Garradin\Users\DynamicFields;
use Garradin\Users\Session;
use Garradin\Users\Users;

use Garradin\Entities\Files\File;

use KD2\SMTP;
use KD2\DB\EntityManager as EM;

#[\AllowDynamicProperties]
class User extends Entity
{
	const NAME = 'Membre';
	const PRIVATE_URL = '!users/details.php?id=%d';

	const MINIMUM_PASSWORD_LENGTH = 8;

	const TABLE = 'users';

	const PREFERENCES = [
		'folders_gallery'   => false,
		'page_size'         => 100,
		'accounting_expert' => false,
		'dark_theme'        => false,
	];

	protected bool $_loading = false;

	public function __construct()
	{
		$this->reloadProperties();

		parent::__construct();
	}

	protected function reloadProperties(): void
	{
		if (empty(self::$_types_cache[static::class])) {
			$types = DynamicField::SYSTEM_FIELDS;

			$fields = DynamicFields::getInstance()->all();

			foreach ($fields as $key => $config) {
				$types[$key] = DynamicField::PHP_TYPES[$config->type];
			}

			self::$_types_cache[static::class] = $types;
		}

		$this->_types = self::$_types_cache[static::class];
		$this->_loading = true;

		foreach ($this->_types as $key => $v) {
			if (!property_exists($this, $key)) {
				$this->$key = null;
			}
		}

		$this->_loading = false;
	}

	public function __wakeup(): void
	{
		$this->reloadProperties();
	}

	public function set(string $key, $value, bool $loose = false, bool $check_for_changes = true) {
		if ($this->_loading && $value === null) {
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
			if (!$field->required) {
				continue;
			}

			$this->assert(null !== $this->{$field->name}, sprintf('"%s" : ce champ est requis', $field->label));
			$this->assert('' !== trim((string)$this->{$field->name}), sprintf('"%s" : ce champ ne peut être vide', $field->label));
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
				throw new UserException('Il n\'est pas possible de supprimer son propre compte. Merci de demander à un autre administrateur de le faire.');
			}
		}

		Files::delete($this->attachementsDirectory());

		return parent::delete();
	}

	public function asArray(bool $for_database = false): array
	{
		$out = parent::asArray($for_database);

		// Remove generated columns
		if ($for_database) {
			foreach (DynamicFields::getInstance()->all() as $field) {
				if ($field->type != 'generated') {
					continue;
				}

				unset($out[$field->name]);
			}
		}

		return $out;
	}

	public function save(bool $selfcheck = true): bool
	{
		$columns = array_intersect(DynamicFields::getInstance()->getSearchColumns(), array_keys($this->_modified));
		$login_field = DynamicFields::getLoginField();
		$login_modified = $this->_modified[$login_field] ?? null;
		$password_modified = $this->_modified['password'] ?? null;

		parent::save($selfcheck);

		// We are not using a trigger as it would make modifying the users table from outside impossible
		// (because the transliterate_to_ascii function does not exist)
		if (count($columns)) {
			DynamicFields::getInstance()->rebuildUserSearchCache($this->id());
		}

		if ($login_modified && $this->password) {
			EmailTemplates::loginChanged($this);
			Log::add(Log::LOGIN_CHANGE, null, $this->id());
		}

		if ($password_modified && $this->password && $this->id == Session::getUserId()) {
			EmailTemplates::passwordChanged($this);
		}

		if ($password_modified) {
			Log::add(Log::LOGIN_PASSWORD_CHANGE, null, $this->id());
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

	public function listFiles(): array
	{
		$files = [];

		foreach (Files::listForContext(File::CONTEXT_USER, (string) $this->id()) as $dir) {
			foreach (Files::list($dir->path) as $file) {
				$files[] = $file;
			}
		}

		return $files;
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

		// Don't allow changing security credentials from form
		unset($source['id_category'], $source['password'], $source['otp_secret'], $source['pgp_key']);

		if (isset($source['id_parent']) && is_array($source['id_parent'])) {
			$source['id_parent'] = Form::getSelectorValue($source['id_parent']);
		}

		return parent::importForm($source);
	}

	public function importSecurityForm(bool $user_mode = true, array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		$allowed = ['password', 'password_check', 'password_confirmed', 'password_delete', 'otp_secret', 'otp_disable', 'pgp_key', 'otp_code'];
		$source = array_intersect_key($source, array_flip($allowed));

		$session = Session::getInstance();

		if ($user_mode && !Session::getInstance()->checkPassword($source['password_check'] ?? null, $this->password)) {
			$this->assert(
				$session->checkPassword($source['password_check'] ?? null, $this->password),
				'Le mot de passe fourni ne correspond pas au mot de passe actuel. Merci de bien vouloir renseigner votre mot de passe courant pour confirmer les changements.'
			);
		}

		if (!empty($source['password_delete'])) {
			$source['password'] = null;
		}
		elseif (isset($source['password'])) {
			$source['password'] = trim($source['password']);

			// Maximum bcrypt password length
			$this->assert(strlen($source['password']) <= 72, sprintf('Le mot de passe doit faire moins de %d caractères.', 72));
			$this->assert(strlen($source['password']) >= self::MINIMUM_PASSWORD_LENGTH, sprintf('Le mot de passe doit faire au moins %d caractères.', self::MINIMUM_PASSWORD_LENGTH));
			$this->assert(hash_equals($source['password'], trim($source['password_confirmed'] ?? '')), 'La vérification du mot de passe doit être identique au mot de passe.');
			$this->assert(!$session->isPasswordCompromised($source['password']), 'Le mot de passe choisi figure dans une liste de mots de passe compromis (piratés), il ne peut donc être utilisé ici. Si vous l\'avez utilisé sur d\'autres sites il est recommandé de le changer sur ces autres sites également.');

			$source['password'] = $session::hashPassword($source['password']);
		}

		if (!empty($source['otp_disable'])) {
			$source['otp_secret'] = null;
		}
		elseif (isset($source['otp_secret'])) {
			$this->assert(trim($source['otp_code'] ?? '') !== '', 'Le code TOTP doit être renseigné pour confirmer l\'opération');
			$this->assert($session->checkOTP($source['otp_secret'], $source['otp_code']), 'Le code TOTP entré n\'est pas valide.');
		}

		if (!empty($source['pgp_key'])) {
			$this->assert($session->getPGPFingerprint($source['pgp_key']), 'Clé PGP invalide : impossible de récupérer l\'empreinte de la clé.');
		}

		// Don't allow user to change password if the password field cannot be changed by user
		if ($user_mode && !$this->canChangePassword()) {
			unset($source['password'], $source['password_check']);
		}

		return parent::importForm($source);
	}

	public function getEmails(): array
	{
		$out = [];

		foreach (DynamicFields::getEmailFields() as $f) {
			if (trim($this->$f)) {
				$out[] = strtolower($this->$f);
			}
		}

		return $out;
	}

	public function canEmail(): bool
	{
		return count($this->getEmails()) > 0;
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

		Emails::queue(Emails::CONTEXT_PRIVATE, [['email' => $this->{$email_field}, 'pgp_key' => $this->pgp_key]], $from, $subject, $message);

		if ($send_copy) {
			Emails::queue(Emails::CONTEXT_PRIVATE, [['email' => $config->org_email, 'pgp_key' => $from->pgp_key]], null, $subject, $message);
		}
	}

	public function checkLoginFieldForUserEdit()
	{
		$session = Session::getInstance();

		if (!$session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)) {
			return;
		}

		$field = DynamicFields::getLoginField();

		if (!$this->isModified($field)) {
			return;
		}

		if (trim($this->$field) !== '') {
			return;
		}

		throw new UserException("Le champ identifiant ne peut être laissé vide pour un administrateur, sinon vous ne pourriez plus vous connecter.");
	}

	public function canChangePassword(): bool
	{
		$password_field = current(DynamicFields::getInstance()->fieldsBySystemUse('password'));
		return $password_field->write_access == $password_field::ACCESS_USER;
	}

	public function checkDuplicate(): ?int
	{
		$id_field = DynamicFields::getNameFieldsSQL();
		$db = DB::getInstance();
		return $db->firstColumn(sprintf('SELECT id FROM %s WHERE %s = ?;', self::TABLE, $id_field), $this->name()) ?: null;
	}

	public function getPreference(string $key)
	{
		return $this->preferences->$key ?? null;
	}

	public function setPreference(string $key, $value)
	{
		settype($value, gettype(self::PREFERENCES[$key]));

		if (null === $this->preferences) {
			$this->preferences = new \stdClass;
		}

		$this->preferences->$key = $value;
		$this->_modified['preferences'] = null;
	}

	public function __destruct()
	{
		if (!($this->isModified('preferences') && count($this->_modified) == 1)) {
			return;
		}

		// Save preferences
		$this->save();
	}
}
