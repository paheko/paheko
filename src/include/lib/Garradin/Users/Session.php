<?php

namespace Garradin\Users;

use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\Users\Users;
use Garradin\UserException;
use Garradin\Plugin;
use Garradin\Users\Emails;

use Garradin\Entities\Users\User;

use const Garradin\SECRET_KEY;
use const Garradin\WWW_URL;
use const Garradin\ADMIN_URL;

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

	const ACCESS_NONE = 0;
	const ACCESS_READ = 1;
	const ACCESS_WRITE = 2;
	const ACCESS_ADMIN = 9;

	// Personalisation de la config de UserSession
	protected $cookie_name = 'gdin';
	protected $remember_me_cookie_name = 'gdinp';
	protected $remember_me_expiry = '+3 months';

	static protected $_instance = null;

	static public function getInstance()
	{
		return self::$_instance ?: self::$_instance = new self;
	}

	public function __clone()
	{
		throw new \LogicException('Cannot clone');
	}

	public function __construct()
	{
		if (self::$_instance !== null) {
			throw new \LogicException('Wrong call, use getInstance');
		}

		$url = parse_url(ADMIN_URL);

		parent::__construct(DB::getInstance(), [
			'cookie_domain' => $url['host'],
			'cookie_path'   => preg_replace('!/admin/$!', '/', $url['path']),
			'cookie_secure' => HTTP::getScheme() == 'https' ? true : false,
		]);
	}

	public function isPasswordCompromised($password)
	{
		if (!isset($this->http)) {
			$this->http = new \KD2\HTTP;
		}

		// Vérifier s'il n'y a pas un plugin qui gère déjà cet aspect
		// notamment en installation mutualisée c'est plus efficace
		$return = ['is_compromised' => null];

		if (Plugin::fireSignal('password.check', ['password' => $password], $return) && isset($return['is_compromised'])) {
			return (bool) $return['is_compromised'];
		}

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
		// Mettre à jour la date de connexion
		$this->db->preparedQuery('UPDATE users SET date_login = datetime() WHERE id = ?;', [$id]);

		$sql = sprintf('SELECT u.*,
			c.perm_connect, c.perm_web, c.perm_users, c.perm_documents,
			c.perm_subscribe, c.perm_accounting, c.perm_config
			FROM users AS u
			INNER JOIN users_categories AS c ON u.id_category = c.id
			WHERE u.id = ? LIMIT 1;',
			$this->db->quoteIdentifier(DynamicFields::getLoginField('u')));

		$u = $this->db->first($sql, $id);

		if (!$u) {
			return null;
		}

		$this->set('permissions', array_filter((array) $u,
			fn($k) => substr($k, 0, 5) == 'perm_',
			\ARRAY_FILTER_USE_KEY)
		);

		$u = array_filter(
			(array) $u,
			fn($k) => substr($k, 0, 5) != 'perm_',
			\ARRAY_FILTER_USE_KEY
		);

		$user = new User;
		$user->load($u);
		$user->exists(true);

		return $user;
	}

	protected function storeRememberMeSelector($selector, $hash, $expiry, $user_id)
	{
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
		return $this->db->first('SELECT selector, hash,
			s.id_user AS user_id, u.password AS user_password, expiry
			FROM users_sessions AS s
			INNER JOIN users AS u ON u.id = s.id_user
			WHERE s.selector = ? LIMIT 1;', $selector);
	}

	protected function deleteRememberMeSelector($selector)
	{
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
		if (!$disable_local_login && defined('\Garradin\LOCAL_LOGIN')) {
			$logged = $this->forceLogin(\Garradin\LOCAL_LOGIN);
		}

		return $logged;
	}

	public function forceLogin(int $id)
	{
		// On va chercher le premier membre avec le droit de gérer la config
		if (-1 === $id) {
			$id = $this->db->firstColumn('SELECT id FROM users
				WHERE id_category IN (SELECT id FROM users_categories WHERE perm_config = ?)
				LIMIT 1', self::ACCESS_ADMIN);
		}

		$logged = parent::isLogged();

		// Only login if required
		if ($id > 0 && (!$logged || ($logged && $this->user->id != $id))) {
			return $this->create($id);
		}

		return $logged;
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
		if (\Garradin\NTP_SERVER
			&& ($time = Security_OTP::getTimeFromNTP(\Garradin\NTP_SERVER))
			&& Security_OTP::TOTP($secret, $code, $time))
		{
			return true;
		}

		return false;
	}

	public function recoverPasswordSend(int $id): void
	{
		$user = $this->fetchUserForPasswordRecovery($id);

		if (!$user) {
			throw new UserException('Aucun membre trouvé avec cette adresse e-mail, ou le membre trouvé n\'a pas le droit de se connecter.');
		}

		if ($user->perm_connect == self::ACCESS_NONE) {
			throw new UserException('Ce membre n\'a pas le droit de se connecter.');
		}

		if (!trim($user->email)) {
			throw new UserException('Ce membre n\'a pas d\'adresse e-mail renseignée dans son profil.');
		}

		$query = $this->makePasswordRecoveryQuery($user);

		$message = "Bonjour,\n\nVous avez oublié votre mot de passe ? Pas de panique !\n\n";
		$message.= "Il vous suffit de cliquer sur le lien ci-dessous pour modifier votre mot de passe.\n\n";
		$message.= ADMIN_URL . 'password.php?c=' . $query;
		$message.= "\n\nSi vous n'avez pas demandé à recevoir ce message, ignorez-le, votre mot de passe restera inchangé.";

		Emails::queue(Emails::CONTEXT_SYSTEM, [$user->email => ['pgp_key' => $user->pgp_key]], null, 'Mot de passe perdu ?', $message);
	}

	protected function fetchUserForPasswordRecovery(int $id): ?\stdClass
	{
		$db = DB::getInstance();

		$id_field = DynamicFields::getLoginField();
		$email_field = DynamicFields::getFirstEmailField();

		// Fetch user, must have an email
		$sql = sprintf('SELECT u.id, u.%s AS email, u.password, u.pgp_key, c.perm_connect
			FROM users u
			INNER JOIN users_categories c ON c.id = u.id_category
			WHERE u.%s = ? COLLATE NOCASE
				AND u.%1$s IS NOT NULL
			LIMIT 1;',
			$db->quoteIdentifier($email_field),
			$db->quoteIdentifier($id_field));

		return $db->first($sql, trim($id));
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
		$user = $this->fetchUserForPasswordRecovery($id);

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

	public function recoverPasswordChange(string $query, string $password, string $password_confirm)
	{
		$user = $this->checkRecoveryPasswordQuery($query);

		if (null === $user) {
			throw new UserException('Le code permettant de changer le mot de passe a expiré. Merci de bien vouloir recommencer la procédure.');
		}

		$ue = Users::get($user->id);
		$ue->importSecurityForm(compact('password', 'password_confirmed'));
		$ue->save();

		$message = "Bonjour,\n\nLe mot de passe de votre compte a bien été modifié.\n\n";
		$message.= "Votre adresse email : ".$user->email."\n";
		$message.= "La demande émanait de l'adresse IP : ".Utils::getIP()."\n\n";
		$message.= "Si vous n'avez pas demandé à changer votre mot de passe, merci de nous le signaler.";

		return Emails::queue(Emails::CONTEXT_SYSTEM, [$user->email => ['pgp_key' => $user->pgp_key]], null, 'Mot de passe modifié', $message);
	}

	public function user(): ?User
	{
		return $this->getUser();
	}

	static public function getUserId(): int
	{
		return self::getInstance()->user()->id;
	}

	public function canAccess(string $category, int $permission): bool
	{
		$permissions = $this->get('permissions');

		if (!$permissions) {
			return false;
		}

		$perm = $permissions['perm_' . $category];

		return ($perm >= $permission);
	}

	public function requireAccess(string $category, int $permission): void
	{
		if (!$this->canAccess($category, $permission))
		{
			throw new UserException('Vous n\'avez pas le droit d\'accéder à cette page.');
		}
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
		return DB::getInstance()->count('users_sessions', 'id_user = ? AND selector != ?', $user->id(), $selector) + 1;
	}
}
