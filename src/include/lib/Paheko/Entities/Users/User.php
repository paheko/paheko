<?php
declare(strict_types=1);

namespace Paheko\Entities\Users;

use KD2\DB\EntityManager;

use Paheko\DB;
use Paheko\Config;
use Paheko\Entity;
use Paheko\Form;
use Paheko\Log;
use Paheko\Template;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\ValidationException;

use Paheko\Files\Files;

use Paheko\Users\Categories;
use Paheko\Email\Emails;
use Paheko\Email\Templates as EmailTemplates;
use Paheko\Users\DynamicFields;
use Paheko\Users\Session;
use Paheko\Users\Users;
use Paheko\Services\Services_User;

use Paheko\Entities\Files\File;

use KD2\SMTP;
use KD2\DB\EntityManager as EM;
use KD2\DB\Date;
use KD2\ZipWriter;

/**
 * WARNING: do not use $user->property = 'value' to set a property value on this class
 * as they will not be saved using save(). Please use $user->set('property', 'value').
 *
 * This is because dynamic properties are set as public, and __set is not called.
 * TODO: change to storing properties in an array
 */
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
		'force_handheld'	=> false,
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
			$this->_types = DynamicField::SYSTEM_FIELDS;

			$fields = DynamicFields::getInstance()->all();

			foreach ($fields as $key => $config) {
				// Fallback to dynamic, if a field type has been deleted
				$this->_types[$key] = DynamicField::PHP_TYPES[$config->type] ?? 'dynamic';
			}
		}
		elseif (empty($this->_types)) {
			$this->_types = self::$_types_cache[static::class];
		}

		self::_loadEntityTypesCache($this->_types);

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

	public function set(string $key, $value) {
		if ($this->_loading && $value === null) {
			$this->$key = $value;
			return;
		}

		// Don't bother for type with virtual columns
		// also don't set it as modified as we don't save the value
		if ($this->_types[$key] === 'dynamic') {
			$this->$key = $value;
			return;
		}

		// Filter double/triple spaces instead of double spaces,
		// to help users who try to log in, see https://fossil.kd2.org/paheko/info/c3295fe0af72e4b3
		// Only when setting a new value
		if (is_string($value) && false !== strpos($value, '  ') && DynamicFields::get($key)->type == 'text') {
			$value = preg_replace('![ ]{2,}!', ' ', $value);
		}

		return parent::set($key, $value);
	}

	public function selfCheck(): void
	{
		$this->assert(!empty($this->id_category), 'Aucune catégorie n\'a été sélectionnée.');

		$df = DynamicFields::getInstance();

		foreach ($df->all() as $field) {
			$value = $this->{$field->name};

			if (!$field->required && null === $value) {
				continue;
			}

			$this->assert(null !== $value, sprintf('"%s" : ce champ est requis', $field->label));

			if (is_bool($value) && $field->required) {
				$this->assert($value === true, sprintf('"%s" : ce champ doit être coché', $field->label));
			}
			elseif (!is_array($value) && !is_object($value) && !is_bool($value)) {
				$this->assert('' !== trim((string)$value), sprintf('"%s" : ce champ ne peut être vide', $field->label));
			}

			if ($field->type === 'email') {
				$this->assert($value === null || SMTP::checkEmailIsValid($value, false), sprintf('"%s" : l\'adresse e-mail "%s" n\'est pas valide.', $field->label, $value));
			}
			elseif ($field->type === 'checkbox') {
				$this->assert($value === false || $value === true, sprintf('"%s" : la valeur de ce champ n\'est pas valide.', $field->label));
			}
		}

		// check user number
		$field = DynamicFields::getNumberField();
		$this->assert($this->$field !== null && ctype_digit((string)$this->$field), 'Numéro de membre invalide : ne peut contenir que des chiffres');

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

		Files::delete($this->attachmentsDirectory());

		return parent::delete();
	}

	public function asArray(bool $for_database = false): array
	{
		$out = parent::asArray($for_database);

		// Remove virtual columns
		if ($for_database) {
			foreach (DynamicFields::getInstance()->all() as $field) {
				if ($field->type == 'virtual') {
					unset($out[$field->name]);
				}
			}
		}

		return $out;
	}

	public function asModuleArray(): array
	{
		$out = $this->asArray();
		$out['_name'] = $this->name();
		$out['_email'] = $this->email();
		$out['_number'] = $this->number();
		$out['_login'] = $this->login();
		return $out;
	}

	public function asDetailsArray(bool $modified_values = false): array
	{
		$list = DynamicFields::getInstance()->listAssocNames();
		$out = [];

		foreach ($list as $key => $label) {
			if ($modified_values && $this->isModified($key)) {
				$out[$key] = $this->getModifiedProperty($key);
			}
			else {
				$out[$key] = $this->$key;
			}

			if ($out[$key] instanceof Date) {
				$out[$key] = $out[$key]->format('d/m/Y');
			}
			elseif ($out[$key] instanceof \DateTimeInterface) {
				$out[$key] = $out[$key]->format('d/m/Y H:i:s');
			}
		}

		return $out;
	}

	public function save(bool $selfcheck = true): bool
	{
		if (!count($this->_modified) && $this->exists()) {
			return true;
		}

		$columns = array_intersect(DynamicFields::getInstance()->getSearchColumns(), array_keys($this->_modified));
		$login_field = DynamicFields::getLoginField();
		$login_modified = $this->_modified[$login_field] ?? null;
		$password_modified = $this->_modified['password'] ?? null;

		$this->set('date_updated', new \DateTime);

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
			Plugins::fire('user.change.password.after', false, ['user' => $this]);
		}

		if ($login_modified) {
			Plugins::fire('user.change.login.after', false, ['user' => $this, 'old_login' => $login_modified]);
		}

		return true;
	}

	public function category(): Category
	{
		return Categories::get($this->id_category);
	}

	public function attachmentsDirectory(): string
	{
		return File::CONTEXT_USER . '/' . $this->id();
	}

	public function listFiles(string $field_name = null): array
	{
		return Files::listForUser($this->id, $field_name);
	}

	public function login(): ?string
	{
		$field = DynamicFields::getLoginField();
		return (string)$this->$field ?: null;
	}

	public function number(): ?string
	{
		$field = DynamicFields::getNumberField();
		return (string)$this->$field ?: null;
	}

	public function setNumberIfEmpty(): void
	{
		$field = DynamicFields::getNumberField();

		if (!empty($this->$field)) {
			return;
		}

		$db = DB::getInstance();
		$new = $db->firstColumn(sprintf('SELECT MAX(%s) + 1 FROM %s WHERE %1$s IS NOT NULL;', $db->quoteIdentifier($field), User::TABLE));
		$this->set($field, $new);
	}

	public function name(): string
	{
		$out = [];

		foreach (DynamicFields::getNameFields() as $key) {
			$out[] = $this->$key;
		}

		return implode(' ', $out);
	}

	public function email(): ?string
	{
		$field = DynamicFields::getFirstEmailField();
		return (string)$this->$field ?: null;
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

		foreach (DynamicFields::getInstance()->fieldsByType('multiple') as $f) {
			if (!isset($source[$f->name . '_present']) && !isset($source[$f->name])) {
				continue;
			}

			$v = 0;

			foreach (array_keys($source[$f->name] ?? []) as $k) {
				$k = 0x01 << $k;
				$v |= $k;
			}

			$source[$f->name] = $v ?: null;
		}

		// Handle unchecked checkbox in HTML form: no value returned
		foreach (DynamicFields::getInstance()->fieldsByType('checkbox') as $f) {
			if (!isset($source[$f->name . '_present']) && !isset($source[$f->name])) {
				continue;
			}

			$source[$f->name] = !empty($source[$f->name]);
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
			if (isset($this->$f) && trim($this->$f)) {
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

		Emails::queue(Emails::CONTEXT_PRIVATE, [$this->{$email_field} => ['pgp_key' => $this->pgp_key]], $from, $subject, $message);

		if ($send_copy) {
			Emails::queue(Emails::CONTEXT_PRIVATE, [$config->org_email], null, $subject, $message);
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
		return $this->preferences->{$key} ?? null;
	}

	public function setPreference(string $key, $value): void
	{
		if (isset($this->$key)) {
			settype($value, gettype(self::PREFERENCES[$key]));
		}

		if (null === $this->preferences) {
			$this->preferences = new \stdClass;
		}

		$this->preferences->{$key} = $value;
		$this->_modified['preferences'] = null;
	}

	public function deletePreference(string $key): void
	{
		if (null === $this->preferences || !isset($this->preferences->{$key})) {
			return;
		}

		unset($this->preferences->{$key});
		$this->_modified['preferences'] = null;
	}

	/**
	 * Save preferences if they have been modified
	 */
	public function __destruct()
	{
		// We can't save preferences if user does not exist (eg. LDAP/Forced Login via LOCAL_LOGIN)
		if (!$this->exists()) {
			return;
		}

		// Nothing to save
		if (!$this->isModified('preferences')) {
			return;
		}


		DB::getInstance()->update(self::TABLE, ['preferences' => json_encode($this->preferences)], 'id = ' . $this->id());
		$this->clearModifiedProperties(['preferences']);
	}

	public function url(): string
	{
		return Utils::getLocalURL(sprintf(self::PRIVATE_URL, $this->id));
	}

	public function diff(): array
	{
		$out = $this->asDetailsArray();

		foreach ($this->_modified as $key => $old) {
			if (!array_key_exists($key, $out)) {
				continue;
			}

			$out[$key] = [$old, $this->$key];
		}

		return $out;
	}

	public function downloadExport(): void
	{
		$services_list = Services_User::perUserList($this->id);
		$services_list->setPageSize(null);

		$export_data = [
			'user'     => $this,
			'services' => $services_list->asArray(true),
		];

		$tpl = Template::getInstance();
		$tpl->assign('user', $this);
		$tpl->assign(compact('services_list'));
		$html = $tpl->fetch('me/export.tpl');

		$name = sprintf('%s - Donnees - %s.zip', Config::getInstance()->get('org_name'), $this->name());
		header('Content-type: application/zip');
		header(sprintf('Content-Disposition: attachment; filename="%s"', $name));

		$zip = new ZipWriter('php://output');
		$zip->setCompression(9);

		$zip->add('info.html', $html);
		$zip->add('info.json', json_encode($export_data));

		foreach ($this->listFiles() as $file) {
			$pointer = $file->getReadOnlyPointer();
			$path = !$pointer ? $file->getLocalFilePath() : null;
			$zip->add($file->path, null, $path, $pointer);

			if ($pointer) {
				fclose($pointer);
			}
		}

		$zip->close();
	}
}
