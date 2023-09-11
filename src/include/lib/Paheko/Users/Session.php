<?php

namespace Paheko\Users;

use Paheko\Config;
use Paheko\DB;
use Paheko\Log;
use Paheko\Utils;
use Paheko\Plugins;
use Paheko\UserException;
use Paheko\ValidationException;

use Paheko\Users\Users;
use Paheko\Email\Templates as EmailsTemplates;
use Paheko\Files\Files;

use Paheko\Entities\Files\File;

use Paheko\Entities\Users\Category;
use Paheko\Entities\Users\User;

use const Paheko\{
	SECRET_KEY,
	WWW_URL,
	ADMIN_URL,
	LOCAL_LOGIN
};

use KD2\Security;
use KD2\Security_OTP;
use KD2\Graphics\QRCode;
use KD2\HTTP;

class Session extends \KD2\UserSession
{
	const SECTION_WEB = 'web';
	const SECTION_DOCUMENTS = 'documents';
	const SECTION_USERS = 'users';
	const SECTION_ACCOUNTING = 'accounting';
	const SECTION_CONNECT = 'connect';
	const SECTION_CONFIG = 'config';
	const SECTION_SUBSCRIBE = 'subscribe';

	const SECTIONS = [
		self::SECTION_WEB,
		self::SECTION_DOCUMENTS,
		self::SECTION_USERS,
		self::SECTION_ACCOUNTING,
		self::SECTION_CONNECT,
		self::SECTION_CONFIG,
	];

	const ACCESS_NONE = 0;
	const ACCESS_READ = 1;
	const ACCESS_WRITE = 2;
	const ACCESS_ADMIN = 9;

	const ACCESS_LEVELS = [
		'none' => self::ACCESS_NONE,
		'read' => self::ACCESS_READ,
		'write' => self::ACCESS_WRITE,
		'admin' => self::ACCESS_ADMIN,
	];

	// Personalisation de la config de UserSession
	protected bool $non_locking = true;
	protected $cookie_name = 'pko';
	protected $remember_me_cookie_name = 'pkop';
	protected $remember_me_expiry = '+3 months';

	protected ?User $_user;
	protected ?array $_permissions;
	protected ?array $_files_permissions;

	static protected $_instance = null;

	static public function getInstance()
	{
		// Use of static is important for Files\WebDAV\Session
		return static::$_instance ?: static::$_instance = new static;
	}

	public function __clone()
	{
		throw new \LogicException('Cannot clone');
	}

	public function __construct()
	{
		if (static::$_instance !== null) {
			throw new \LogicException('Wrong call, use getInstance');
		}

		$url = parse_url(ADMIN_URL);

		parent::__construct(DB::getInstance(), [
			'cookie_domain' => $url['host'],
			'cookie_path'   => preg_replace('!/admin/$!', '/', $url['path']),
			'cookie_secure' => HTTP::getScheme() == 'https' ? true : false,
		]);

		$this->sid_in_url_secret = '&spko=' . sha1(SECRET_KEY);
	}

	public function isPasswordCompromised($password)
	{
		if (!isset($this->http)) {
			$this->http = new \KD2\HTTP;
		}

		// Vérifier s'il n'y a pas un plugin qui gère déjà cet aspect
		// notamment en installation mutualisée c'est plus efficace
		$signal = Plugins::fire('password.check', true, ['password' => $password]);

		if ($signal && $signal->isStopped()) {
			return (bool) $signal->getOut('is_compromised');
		}

		unset($signal);

		return parent::isPasswordCompromised($password);
	}

	protected function getUserForLogin($login)
	{
		$id_field = DynamicFields::getLoginField();

		// Ne renvoie un membre que si celui-ci a le droit de se connecter
		$query = 'SELECT u.id, u.%1$s AS login, u.password, u.otp_secret
			FROM users AS u
			INNER JOIN users_categories AS c ON c.id = u.id_category
			WHERE u.%1$s = ? COLLATE NOCASE AND c.perm_connect >= %2$d
			LIMIT 1;';

		$query = sprintf($query, $id_field, self::ACCESS_READ);

		return $this->db->first($query, $login);
	}

	protected function getUserDataForSession($id)
	{
		$user = Users::get($id);

		if (!$user) {
			return null;
		}

		return $id;
	}

	protected function storeRememberMeSelector($selector, $hash, $expiry, $user_id)
	{
		$selector = $this->cookie_name . '_' . $selector;
		return $this->db->insert('users_sessions', [
			'selector'  => $selector,
			'hash'      => $hash,
			'expiry'    => $expiry,
			'id_user'   => $user_id,
		]);
	}

	protected function expireRememberMeSelectors()
	{
		return $this->db->delete('users_sessions', $this->db->where('expiry', '<', time()));
	}

	protected function getRememberMeSelector($selector)
	{
		$selector = $this->cookie_name . '_' . $selector;
		return $this->db->first('SELECT REPLACE(selector, ?, \'\') AS selector, hash,
			s.id_user AS user_id, u.password AS user_password, expiry
			FROM users_sessions AS s
			LEFT JOIN users AS u ON u.id = s.id_user
			WHERE s.selector = ? LIMIT 1;', $this->cookie_name . '_', $selector);
	}

	protected function deleteRememberMeSelector($selector)
	{
		$selector = $this->cookie_name . '_' . $selector;
		return $this->db->delete('users_sessions', $this->db->where('selector', $selector));
	}

	protected function deleteAllRememberMeSelectors($user_id)
	{
		return $this->db->delete('users_sessions', $this->db->where('id_user', $user_id));
	}

	public function isLogged(bool $disable_local_login = false)
	{
		$logged = parent::isLogged();

		// Ajout de la gestion de LOCAL_LOGIN
		if (!$logged && !$disable_local_login && LOCAL_LOGIN) {
			$logged = $this->forceLogin(LOCAL_LOGIN);
		}

		return $logged;
	}

	public function forceLogin($login)
	{
		// Force login with a static user, that is not in the local database
		// this is useful for using a SSO like LDAP for example
		if (is_array($login)) {
			$this->_user = (new User)->import($login['user'] ?? []);

			if (isset($login['user']['_name'])) {
				$name = DynamicFields::getFirstNameField();
				$this->_user->$name = $login['user']['_name'];
			}

			$this->_permissions = [];

			foreach (Category::PERMISSIONS as $perm => $data) {
				$this->_permissions[$perm] = $login['permissions'][$perm] ?? self::ACCESS_NONE;
			}

			return true;
		}

		// Look for the first user with the permission to manage the configuration
		if (-1 === $login) {
			$login = $this->db->firstColumn('SELECT id FROM users
				WHERE id_category IN (SELECT id FROM users_categories WHERE perm_config = ?)
				LIMIT 1', self::ACCESS_ADMIN);
		}

		// Only login if required
		if ($login > 0 && ($this->user ?? null) != $login) {
			return $this->create($login);
		}

		return isset($this->user) ? true : false;
	}

	public function login($login, $password, $remember_me = false)
	{
		$success = parent::login($login, $password, $remember_me);
		$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 150) ?: null;

		if (true === $success) {
			Log::add(Log::LOGIN_SUCCESS, compact('user_agent'));

			// Mettre à jour la date de connexion
			$this->db->preparedQuery('UPDATE users SET date_login = datetime() WHERE id = ?;', [$this->getUser()->id]);
		}
		// $success can be 'OTP' as well
		elseif (!$success) {
			if ($user = $this->getUserForLogin($login)) {
				Log::add(Log::LOGIN_FAIL, compact('user_agent'), $user->id);
			}
			else {
				Log::add(Log::LOGIN_FAIL, compact('user_agent'));
			}
		}

		Plugins::fire('user.login.after', false, compact('login', 'password', 'remember_me', 'success'));

		// Clean up logs
		Log::clean();

		return $success;
	}

	public function loginOTP(string $code): bool
	{
		$this->start();
		$user_id = $_SESSION['userSessionRequireOTP']->user->id ?? null;
		$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 150) ?: null;
		$details = compact('user_agent') + ['otp' => true];

		$success = parent::loginOTP($code);

		if ($success) {
			Log::add(Log::LOGIN_SUCCESS, $details, $user_id);

			// Mettre à jour la date de connexion
			$this->db->preparedQuery('UPDATE users SET date_login = datetime() WHERE id = ?;', [$user_id]);
		}
		else {
			Log::add(Log::LOGIN_FAIL, $details, $user_id);
		}

		Plugins::fire('user.login.otp', false, compact('success', 'user_id'));

		return $success;
	}

	public function logout(bool $all = false)
	{
		$this->_user = null;
		$this->_permissions = null;
		$this->_files_permissions = null;

		return parent::logout();
	}

	public function recoverPasswordSend(string $id): void
	{
		$user = $this->fetchUserForPasswordRecovery($id);

		if (!$user) {
			throw new UserException('Aucun membre trouvé avec cette adresse e-mail, ou le membre trouvé n\'a pas le droit de se connecter.');
		}

		if ($user->perm_connect == self::ACCESS_NONE) {
			throw new UserException('Ce membre n\'a pas le droit de se connecter.');
		}

		$email = DynamicFields::getFirstEmailField();

		if (!trim($user->$email)) {
			throw new UserException('Ce membre n\'a pas d\'adresse e-mail renseignée dans son profil.');
		}

		$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 150) ?: null;
		Log::add(Log::LOGIN_RECOVER, compact('user_agent'), $user->id);

		$query = $this->makePasswordRecoveryQuery($user);

		$url = ADMIN_URL . 'password.php?c=' . $query;

		EmailsTemplates::passwordRecovery($user->$email, $url, $user->pgp_key);
	}

	protected function fetchUserForPasswordRecovery(string $identifier, ?string $identifier_field = null): ?\stdClass
	{
		$db = DB::getInstance();

		$identifier_field ??= DynamicFields::getLoginField();
		$email_field = DynamicFields::getFirstEmailField();

		if ($identifier_field === 'id') {
			$identifier = (int) $identifier;
		}
		else {
			$identifier = trim($identifier);
		}

		// Fetch user, must have an email
		$sql = sprintf('SELECT u.id, u.%s AS email, u.password, u.pgp_key, c.perm_connect
			FROM users u
			INNER JOIN users_categories c ON c.id = u.id_category
			WHERE u.%s = ? COLLATE U_NOCASE
				AND u.%1$s IS NOT NULL
			LIMIT 1;',
			$db->quoteIdentifier($email_field),
			$db->quoteIdentifier($identifier_field));

		return $db->first($sql, $identifier) ?: null;
	}

	protected function makePasswordRecoveryHash(\stdClass $user, ?int $expire = null): string
	{
		// valide pour 1 heure minimum
		$expire = $expire ?? ceil((time() - strtotime('2017-01-01')) / 3600) + 1;

		$hash = hash_hmac('sha256', $user->email . $user->id . $user->password . $expire, SECRET_KEY, true);
		$hash = substr(Security::base64_encode_url_safe($hash), 0, 16);
		return $hash;
	}

	protected function makePasswordRecoveryQuery(\stdClass $user): string
	{
		$expire = ceil((time() - strtotime('2017-01-01')) / 3600) + 1;
		$hash = $this->makePasswordRecoveryHash($user, $expire);
		$id = base_convert($user->id, 10, 36);
		$expire = base_convert($expire, 10, 36);
		return sprintf('%s.%s.%s', $id, $expire, $hash);
	}

	/**
	 * Check that the supplied query is valid, if so, return the user information
	 * @param  string $query User-supplied query
	 */
	public function checkRecoveryPasswordQuery(string $query): ?\stdClass
	{
		if (substr_count($query, '.') !== 2) {
			return null;
		}

		list($id, $expire, $email_hash) = explode('.', $query);

		$id = base_convert($id, 36, 10);
		$expire = base_convert($expire, 36, 10);

		$expire_timestamp = ($expire * 3600) + strtotime('2017-01-01');

		// Check that the query has not expired yet
		if (time() / 3600 > $expire_timestamp) {
			return null;
		}

		// Fetch user info
		$user = $this->fetchUserForPasswordRecovery($id, 'id');

		if (!$user) {
			return null;
		}

		// Check hash using secret data from the user
		$hash = $this->makePasswordRecoveryHash($user, $expire);

		if (!hash_equals($hash, $email_hash)) {
			return null;
		}

		return $user;
	}

	public function recoverPasswordChange(string $query, string $password, string $password_confirmed)
	{
		$user = $this->checkRecoveryPasswordQuery($query);

		if (null === $user) {
			throw new UserException('Le code permettant de changer le mot de passe a expiré. Merci de bien vouloir recommencer la procédure.');
		}

		$ue = Users::get($user->id);
		$ue->importSecurityForm(false, compact('password', 'password_confirmed'));
		$ue->save();
		EmailsTemplates::passwordChanged($ue);
	}

	public function user(): ?User
	{
		return $this->getUser();
	}

	static public function getPreference(string $key)
	{
		return self::getLoggedUser()->getPreference($key);
	}

	static public function getLoggedUser(): ?User
	{
		$s = self::getInstance();

		if (!$s->isLogged()) {
			return null;
		}

		return $s->getUser();
	}

	/**
	 * Returns cookie string for PDF printing
	 */
	static public function getCookie(): ?string
	{
		$i = self::getInstance();

		if (!$i->isLogged()) {
			return null;
		}

		return sprintf('%s=%s', $i->cookie_name, $i->id());
	}

	static public function getCookieSecret(): string
	{
		return self::getInstance()->sid_in_url_secret;
	}

	public function getUser()
	{
		if (isset($this->_user)) {
			return $this->_user;
		}

		if (!$this->isLogged())
		{
			throw new \LogicException('User is not logged in.');
		}

		$this->_user = Users::get($this->user);
		$this->_permissions = null;
		$this->_files_permissions = null;
		return $this->_user;
	}

	static public function getUserId(): ?int
	{
		$i = self::getInstance();

		if (!$i->isLogged()) {
			return null;
		}

		return $i->user;
	}

	public function canAccess(string $section, int $permission): bool
	{
		if (!$this->isLogged()) {
			return false;
		}

		if (!isset($this->_permissions)) {
			$this->_permissions = $this->user()->category()->getPermissions();
		}

		$perm = $this->_permissions[$section];

		return ($perm >= $permission);
	}

	public function requireAccess(string $section, int $permission): void
	{
		if (!$this->canAccess($section, $permission)) {
			throw new UserException('Vous n\'avez pas le droit d\'accéder à cette page.');
		}
	}

	public function checkExtensionFilePermissions(string $path, string $permission): bool
	{
		$context = strtok($path, '/');
		$type = strtok('/');
		$name = strtok('/');
		$file_path = strtok(false);

		if (empty($name) || empty($type) || ($type !== 'm' && $type !== 'p') || empty($file_path)) {
			return false;
		}

		$public = substr($file_path, 0, 7) === 'public/';

		$base = $context . '/' . $type . '/' . $name . '/';

		if ($public) {
			$base .= 'public/';
		}

		// Build cache
		if (!isset($this->_files_permissions[$base])) {
			$read = $write = false;
			// Public files
			if (!$this->isLogged() && $public) {
				$read = true;
			}
			// Other files are private
			elseif ($this->isLogged()) {
				if ('p' === $type) {
					$ext = Plugins::get($name);
				}
				else {
					$ext = Modules::get($name);
				}

				$read = $write = $ext->restrict_section ? $this->canAccess($ext->restrict_section, $ext->restrict_level) : false;
			}

			$this->_files_permissions[$base] = [
				'mkdir'  => $write,
				'move'   => $write,
				'write'  => $write,
				'create' => $write,
				'delete' => $write,
				'read'   => $write,
				'share'  => $write,
			];
		}

		return $this->_files_permissions[$base][$permission];
	}

	public function checkFilePermission(string $path, string $permission): bool
	{
		$path_level = preg_replace('!/[^/]+!', '/', $path);
		$path = ltrim($path, '/');

		if (!isset($this->_files_permissions)) {
			$this->_files_permissions = Files::buildUserPermissions($this);
		}

		// Check permissions for plugins and modules files
		if (strpos($path, File::CONTEXT_EXTENSIONS . '/') === 0) {
			return $this->checkExtensionFilePermissions($path, $permission);
		}

		foreach ($this->_files_permissions as $context => $permissions) {
			if (!array_key_exists($permission, $permissions)) {
				throw new \InvalidArgumentException(sprintf('Unknown permission "%s" in context "%s"', $permission, $context));
			}

			if ($context == $path
				|| 0 === strpos($path, $context)
				|| 0 === strpos($path_level, $context)) {
				return $permissions[$permission];
			}
		}

		throw new \InvalidArgumentException(sprintf('Unknown context: %s', $path));
	}

	public function getFilePermissions(string $path): ?array
	{
		if (!isset($this->_files_permissions)) {
			$this->_files_permissions = Files::buildUserPermissions($this);
		}

		return $this->_files_permissions[$path] ?? null;
	}

	public function requireFilePermission(string $context, string $permission)
	{
		if (!$this->checkFilePermission($context, $permission)) {
			throw new UserException('Vous n\'avez pas le droit d\'effectuer cette action.');
		}
	}

	// Ici checkOTP utilise NTP en second recours
	public function checkOTP($secret, $code)
	{
		if (Security_OTP::TOTP($secret, $code))
		{
			return true;
		}

		// Vérifier encore, mais avec le temps NTP
		// au cas où l'horloge du serveur n'est pas à l'heure
		if (\Paheko\NTP_SERVER
			&& ($time = Security_OTP::getTimeFromNTP(\Paheko\NTP_SERVER))
			&& Security_OTP::TOTP($secret, $code, $time))
		{
			return true;
		}

		return false;
	}

	public function getNewOTPSecret()
	{
		$config = Config::getInstance();
		$out = [];
		$out['secret'] = Security_OTP::getRandomSecret();
		$out['secret_display'] = implode(' ', str_split($out['secret'], 4));

		$icon = $config->fileURL('icon');
		$out['url'] = Security_OTP::getOTPAuthURL($config->org_name, $out['secret'], 'totp', $icon);

		$qrcode = new QRCode($out['url']);
		$out['qrcode'] = 'data:image/svg+xml;base64,' . base64_encode($qrcode->toSVG());

		return $out;
	}

	public function countActiveSessions(): int
	{
		$selector = $this->getRememberMeCookie()->selector ?? null;
		$user = $this->getUser();
		return DB::getInstance()->count('users_sessions', 'id_user = ? AND selector != ?', $user->id(), $this->cookie_name . '_' . $selector) + 1;
	}

	public function isAdmin(): bool
	{
		return $this->canAccess(self::SECTION_CONNECT, self::ACCESS_READ)
			&& $this->canAccess(self::SECTION_CONFIG, self::ACCESS_ADMIN);
	}
}
