<?php

namespace Garradin\Users;

use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\Users\Users;
use Garradin\UserException;
use Garradin\Plugin;
use Garradin\Users\Emails;

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

	const MINIMUM_PASSWORD_LENGTH = 8;

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

	static public function checkPasswordValidity($password)
	{
		if (strlen($password) < self::MINIMUM_PASSWORD_LENGTH)
		{
			throw new UserException(sprintf('Le mot de passe doit faire au moins %d caractères.', self::MINIMUM_PASSWORD_LENGTH));
		}

		$session = self::getInstance();
		$session->http = new HTTP;

		if ($session->isPasswordCompromised($password)) {
			throw new UserException('Ce mot de passe figure dans une liste de mots de passe compromis, il ne peut donc être utilisé ici. Si vous l\'avez utilisé sur d\'autres sites il est recommandé de le changer sur ces autres sites également.');
		}
	}

	public function isPasswordCompromised($password)
	{
		// Vérifier s'il n'y a pas un plugin qui gère déjà cet aspect
		// notamment en installation mutualisée c'est plus efficace
		$return = ['is_compromised' => null];
		$called = Plugin::fireSignal('motdepasse.compromis', ['password' => $password], $return);

		if ($called !== null) {
			return $return['is_compromised'];
		}

		return parent::isPasswordCompromised($password);
	}

	protected function getUserForLogin($login)
	{
		$champ_id = DynamicFields::getLoginField();

		// Ne renvoie un membre que si celui-ci a le droit de se connecter
		$query = 'SELECT u.id, m.%1$s AS login, m.password, m.secret_otp AS otp_secret
			FROM users AS u
			INNER JOIN users_categories AS c ON c.id = m.id_category
			WHERE u.%1$s = ? COLLATE NOCASE AND c.perm_connect >= %2$d
			LIMIT 1;';

		$query = sprintf($query, $champ_id, self::ACCESS_READ);

		return $this->db->first($query, $login);
	}

	protected function getUserDataForSession($id)
	{
		// Mettre à jour la date de connexion
		$this->db->preparedQuery('UPDATE users SET date_login = datetime() WHERE id = ?;', [$id]);

		$sql = sprintf('SELECT u.*, %s AS _name,
			c.perm_connect, c.perm_web, c.perm_users, c.perm_documents,
			c.perm_subscribe, c.perm_accounting, c.perm_config
			FROM users AS u
			INNER JOIN users_categories AS c ON u.id_category = c.id
			WHERE u.id = ? LIMIT 1;',
			$this->db->quoteIdentifier(DynamicFields::getLoginField('u')));

		return $this->db->first($sql, $id);
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

	public function getOTPSecret($secret = null)
	{
		if (!$secret)
		{
			$secret = Security_OTP::getRandomSecret();
		}

		$out = [];
		$out['secret'] = $secret;
		$out['secret_display'] = implode(' ', str_split($secret, 4));
		$out['url'] = Security_OTP::getOTPAuthURL(Config::getInstance()->get('org_name'), $secret);

		$qrcode = new QRCode($out['url']);
		$out['qrcode'] = 'data:image/svg+xml;base64,' . base64_encode($qrcode->toSVG());

		return $out;
	}

	public function recoverPasswordSend(int $id)
	{
		$user = $this->fetchUserForPasswordRecovery($id);

		if (!$user) {
			return false;
		}

		$query = $this->makePasswordRecoveryQuery($user);

		$message = "Bonjour,\n\nVous avez oublié votre mot de passe ? Pas de panique !\n\n";
		$message.= "Il vous suffit de cliquer sur le lien ci-dessous pour modifier votre mot de passe.\n\n";
		$message.= ADMIN_URL . 'password.php?c=' . $query;
		$message.= "\n\nSi vous n'avez pas demandé à recevoir ce message, ignorez-le, votre mot de passe restera inchangé.";

		if ($membre->clef_pgp) {
			$content = Security::encryptWithPublicKey($membre->clef_pgp, $message);
		}

		Emails::queue(Emails::CONTEXT_SYSTEM, [$membre->email => null], null, 'Mot de passe perdu ?', $message);
		return true;
	}

	protected function fetchUserForPasswordRecovery(int $id): ?\stdClass
	{
		$db = DB::getInstance();

		$id_field = DynamicFields::getLoginField();
		$email_field = DynamicFields::getFirstEmailField();

		// Fetch user, must have an email
		$sql = sprintf('SELECT id, %s AS email, password, pgp_key
			FROM users
			WHERE %s = ? COLLATE NOCASE
				AND %1$s IS NOT NULL
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
		$user = $this->checkRecoveryPasswordQuery($code);

		if (null === $user) {
			throw new UserException('Le code permettant de changer le mot de passe a expiré. Merci de bien vouloir recommencer la procédure.');
		}

		$password = trim($password);
		$password_confirm = trim($password_confirm);

		if (!hash_equals($password, $password_confirm)) {
			throw new UserException('Le mot de passe et sa vérification ne sont pas identiques.');
		}

		self::checkPasswordValidity($password);

		$password = self::hashPassword($password);

		$message = "Bonjour,\n\nLe mot de passe de votre compte a bien été modifié.\n\n";
		$message.= "Votre adresse email : ".$user->email."\n";
		$message.= "La demande émanait de l'adresse IP : ".Utils::getIP()."\n\n";
		$message.= "Si vous n'avez pas demandé à changer votre mot de passe, merci de nous le signaler.";

		DB::getInstance()->update('users', ['password' => $password], 'id = :id', ['id' => (int)$user->id]);

		return Emails::queue(Emails::CONTEXT_SYSTEM, [$membre->email => null], null, 'Mot de passe changé', $message);
	}

	public function editUser($data) // FIXME update
	{
		(new Membres)->edit($this->user->id, $data, false);
		$this->refresh();

		return true;
	}

	public function getUser()
	{
		$user = parent::getUser();

		// Force refresh of session when it's too old (FIXME: remove at version 1.2+)
		if (!property_exists($this->user, 'perm_users')) {
			$this->refresh();
			$user = $this->getUser();
		}

		return $user;
	}

	static public function getUserId()
	{
		return self::getInstance()->getUser()->id;
	}

	public function canAccess($category, $permission)
	{
		if (!$this->getUser())
		{
			return false;
		}

		$perm_name = 'perm_' . $category;
		$perm = $this->getUser()->$perm_name;

		return ($perm >= $permission);
	}

	public function requireAccess($category, $permission)
	{
		if (!$this->canAccess($category, $permission))
		{
			throw new UserException('Vous n\'avez pas le droit d\'accéder à cette page.');
		}
	}

	public function getNewOTPSecret()
	{
		$out = [];
		$out['secret'] = Security_OTP::getRandomSecret();
		$out['secret_display'] = implode(' ', str_split($out['secret'], 4));
		$out['url'] = Security_OTP::getOTPAuthURL(Config::getInstance()->get('org_name'), $out['secret']);

		$qrcode = new QRCode($out['url']);
		$out['qrcode'] = 'data:image/svg+xml;base64,' . base64_encode($qrcode->toSVG());

		return $out;
	}

	public function sendMessage($dest, $sujet, $message, $copie = false)
	{
		$user = $this->getUser();

		$content = "Ce message vous a été envoyé par :\n";
		$content.= sprintf("%s\n%s\n\n", $user->identite, $user->email);
		$content.= str_repeat('=', 70) . "\n\n";
		$content.= $message;

		$dest = $copie ? [$dest => null, $user->email => null] : [$dest => null];

		return Emails::queue(Emails::CONTEXT_PRIVATE, $dest, null, $sujet, $content);
	}

	/**
	 * Change self security data
	 * @param  \stdClass $data Security data, eg. password, pgp_key or otp_secret
	 */
	public function editSecurity(\stdClass $data): void
	{
		$user = Users::get($this->user->id);

		$allowed_fields = ['password', 'pgp_key', 'otp_secret'];
		$data = array_intersect_key($data, array_flip($allowed_fields));

		if (isset($data->password) && trim($data->password) !== '') {
			self::checkPasswordValidity($data->password);
			$user->set('password', self::hashPassword($data->password));
		}

		if (isset($data->pgp_key) && trim($data->pgp_key) !== '') {
			$data->pgp_key = trim($data->pgp_key);

			if (!$this->getPGPFingerprint($data->pgp_key)) {
				throw new UserException('Clé PGP invalide : impossible d\'extraire l\'empreinte.');
			}

			$user->set('pgp_key', $data->pgp_key);
		}

		if (!empty($data->otp_secret)) {
			$user->set('otp_secret', $otp_secret);
		}

		$this->refresh();
	}
}
