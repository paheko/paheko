<?php

namespace Garradin\Membres;

use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\Membres;

use \KD2\Security;
use \KD2\Security_OTP;
use \KD2\QRCode;

class Session
{
	const HASH_ALGO = 'sha256';
	const REQUIRE_OTP = 'otp';

	protected $cookie;
	protected $user;
	protected $id;

	const SESSION_COOKIE_NAME = 'gdin';
	const PERMANENT_COOKIE_NAME = 'gdinp';

	static protected function getSessionOptions()
	{
		$url = parse_url(\Garradin\WWW_URL);

		return [
			'name'            => self::SESSION_COOKIE_NAME,
			'cookie_path'     => $url['path'],
			'cookie_domain'   => $url['host'],
			'cookie_secure'   => (\Garradin\PREFER_HTTPS >= 2) ? true : false,
			'cookie_httponly' => true,
		];
	}

	static protected function start($write = false)
	{
		// Don't start session if it has been already started
		if (isset($_SESSION))
		{
			return true;
		}

		// Only start session if it exists
		if (!$write && !isset($_COOKIE[self::SESSION_COOKIE_NAME]))
		{
			return false;
		}

		return session_start(self::getSessionOptions());
	}

	static public function refresh()
	{
		return self::start();
	}

	static public function get()
	{
		try {
			return new Session;
		}
		catch (\LogicException $e) {
			throw $e;
			return false;
		}
	}

	static public function login($id, $passe, $permanent = false)
	{
		assert(is_bool($permanent));
		assert(is_string($id));
		assert(is_string($passe));

		$db = DB::getInstance();
		$champ_id = Config::getInstance()->get('champ_identifiant');

		$query = 'SELECT id, passe, secret_otp,
			(SELECT droit_connexion FROM membres_categories AS mc WHERE mc.id = id_categorie) AS droit_connexion
			FROM membres WHERE %s = ? LIMIT 1;';

		$query = sprintf($query, $champ_id);

		$membre = $db->first($query, trim($id));

		// Membre non trouvé
		if (empty($membre))
		{
			return false;
		}

		// vérification du mot de passe
		if (!Membres::checkPassword(trim($passe), $membre->passe))
		{
			return false;
		}

		// vérification que le membre a le droit de se connecter
		if ($membre['droit_connexion'] == Membres::DROIT_AUCUN)
		{
			return false;
		}

		if ($membre['secret_otp'])
		{
			self::start();

			$_SESSION = [];

			$_SESSION['otp'] = (object) [
				'id'        => (int) $membre->id,
				'secret'    => $membre->secret_otp,
				'permanent' => $permanent,
			];

			return self::REQUIRE_OTP;
		}
		else
		{
			return self::create((int) $membre->id, $permanent);
		}
	}

	/**
	 * Créer une session permanente "remember me"
	 * @param  \stdClass $user
	 * @return boolean
	 */
	static protected function createPermanentSession(\stdClass $user)
	{
		$selector = hash(self::HASH_ALGO, Security::random_bytes(10));
		$token = hash(self::HASH_ALGO, Security::random_bytes(10));
		$expire = (new DateTime)->modify('+3 months');

		DB::getInstance()->insert('membres_sessions', [
			'selecteur' => $selector,
			'token'     => $token,
			'expire'    => $expire,
			'id_membre' => $user->id,
		]);

		$token = hash(self::HASH_ALGO, $token . $user->passe);
		$cookie = $selector . '|' . $token;

		setcookie(self::PERMANENT_COOKIE_NAME, $cookie, $expire->getTimestamp(),
			$url['path'], $url['host'], (\Garradin\PREFER_HTTPS >= 2), true);

		return true;
	}

	static public function isOTPRequired()
	{
		self::start();

		return !empty($_SESSION['otp']);
	}

	static public function loginOTP($code)
	{
		self::start();

		if (empty($_SESSION['otp']))
		{
			return false;
		}

		$user = $_SESSION['otp'];

		if (empty($user->secret) || empty($user->id))
		{
			return false;
		}

		if (!Security_OTP::TOTP($user->secret, $code))
		{
			// Vérifier encore, mais avec le temps NTP
			// au cas où l'horloge du serveur n'est pas à l'heure
			$time = Security_OTP::getTimeFromNTP('fr.pool.ntp.org');

			if (!Security_OTP::TOTP($user->secret, $code, $time))
			{
				return false;
			}
		}

		$session = new Session($user->id);
		$session->updateLoginDate();
		return $session;
	}

	static public function recoverPasswordCheck($id)
	{
		$db = DB::getInstance();
		$config = Config::getInstance();

		$champ_id = $config->get('champ_identifiant');

		$membre = $db->first('SELECT id, email FROM membres WHERE '.$champ_id.' = ? LIMIT 1;', trim($id));

		if (!$membre || trim($membre['email']) == '')
		{
			return false;
		}

		self::start(true);
		$hash = sha1($membre['email'] . $membre['id'] . 'recover' . ROOT . time());
		$_SESSION['recover_password'] = [
			'id' => (int) $membre['id'],
			'email' => $membre['email'],
			'hash' => $hash
		];

		$message = "Bonjour,\n\nVous avez oublié votre mot de passe ? Pas de panique !\n\n";
		$message.= "Il vous suffit de cliquer sur le lien ci-dessous pour recevoir un nouveau mot de passe.\n\n";
		$message.= WWW_URL . 'admin/password.php?c=' . substr($hash, -10);
		$message.= "\n\nSi vous n'avez pas demandé à recevoir ce message, ignorez-le, votre mot de passe restera inchangé.";

		return Utils::mail($membre['email'], '['.$config->get('nom_asso').'] Mot de passe perdu ?', $message);
	}

	static public function recoverPasswordConfirm($hash)
	{
		self::start();

		if (empty($_SESSION['recover_password']['hash']))
			return false;

		if (substr($_SESSION['recover_password']['hash'], -10) != $hash)
			return false;

		$config = Config::getInstance();
		$db = DB::getInstance();

		$password = Utils::suggestPassword();

		$dest = $_SESSION['recover_password']['email'];
		$id = (int)$_SESSION['recover_password']['id'];

		$message = "Bonjour,\n\nVous avez demandé un nouveau mot de passe pour votre compte.\n\n";
		$message.= "Votre adresse email : ".$dest."\n";
		$message.= "Votre nouveau mot de passe : ".$password."\n\n";
		$message.= "Si vous n'avez pas demandé à recevoir ce message, merci de nous le signaler.";

		$password = $this->_hashPassword($password);

		$db->update('membres', ['passe' => $password], 'id = :id', ['id' => (int)$id]);

		return Utils::mail($dest, '['.$config->get('nom_asso').'] Nouveau mot de passe', $message);
	}

	public function __construct()
	{
		if (defined('\Garradin\LOCAL_LOGIN') && is_int(\Garradin\LOCAL_LOGIN) && \Garradin\LOCAL_LOGIN > 0)
		{
			$this->id = \Garradin\LOCAL_LOGIN;

			if (empty($_SESSION['user']))
			{
				$this->populateUserData();
			}
		}

		$this->autoLogin();

		// Démarrage session
		self::start();

		if (empty($_SESSION['user']))
		{
			throw new \LogicException('Aucun utilisateur connecté.');
		}

		$this->user = $_SESSION['user'];
	}

	protected function getPermanentCookie()
	{
		if (empty($_COOKIE[self::PERMANENT_COOKIE_NAME]))
		{
			return false;
		}

		$cookie = $_COOKIE[self::PERMANENT_COOKIE_NAME];

		$data = explode('|', $cookie);

		if (count($data) !== 2)
		{
			return false;
		}

		return (object) [
			'selector' => $data[0],
			'token'    => $data[1],
		];
	}

	/**
	 * Connexion automatique en utilisant un cookie permanent
	 * (fonction "remember me")
	 *
	 * @link   https://www.databasesandlife.com/persistent-login/
	 * @link   https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
	 * @link   http://jaspan.com/improved_persistent_login_cookie_best_practice
	 * @return boolean
	 */
	protected function autoLogin()
	{
		$cookie = $this->getPermanentCookie();

		if (!$cookie)
		{
			return false;
		}

		$db = DB::getInstance();
		$row = $db->first('SELECT ms.token, ms.id_membre, 
			strftime("%s", ms.expire) AS expire, membres.passe
			INNER JOIN membres ON membres.id = ms.id_membre
			FROM membres_sessions AS ms WHERE ms.selecteur = ?;',
			$cookie->selector);

		if ($row->expire < time())
		{
			return $this->logout();
		}

		// On utilise le mot de passe: si l'utilisateur change de mot de passe
		// toutes les sessions précédentes sont invalidées
		$hash = hash(self::HASH_ALGO, $row->token . $row->passe);

		// Vérification du token
		if (!Security::hash_equals($cookie->token, $row->token))
		{
			// Le sélecteur est valide, mais pas le token ?
			// c'est probablement que le cookie a été volé, qu'un attaquant
			// a obtenu un nouveau token, et que l'utilisateur se représente 
			// avec un token qui n'est plus valide.
			// Dans ce cas supprimons toutes les sessions de ce membre pour 
			// le forcer à se re-connecter
			$db->delete('membres_sessions', 'id_membre = :id', ['id' => (int) $row->id_membre]);

			return $this->logout();
		}

		// Re-générons un nouveau token et mettons à jour le cookie
		$token = hash(self::HASH_ALGO, Security::random_bytes(10));
		$expire = (new DateTime)->modify('+3 months');

		$db->update('membres_sessions', [
			'token'     => $token,
			'expire'    => $expire,
		], 'selecteur = :selecteur AND id_membre = :id_membre', [
			'selecteur' => $cookie->selector,
			'id_membre' => $row->id_membre,
		]);

		$new_cookie = $cookie->selector . '|' . $token;

		setcookie(self::PERMANENT_COOKIE_NAME, $new_cookie, $expire->getTimestamp(),
			$url['path'], $url['host'], (\Garradin\PREFER_HTTPS >= 2), true);


		$this->id = $row->id_membre;

		$this->populateUserData();

		return true;
	}

	public function logout()
	{
		$url = parse_url(\Garradin\WWW_URL);

		if ($cookie = $this->getPermanentCookie())
		{
			// Suppression de cette session permanente
			DB::getInstance()->delete('membres_sessions', 'selecteur = ?', $cookie->selector);

			setcookie(self::PERMANENT_COOKIE_NAME, null, -1, $url['path'], $url['host'], false, true);
			unset($_COOKIE[self::PERMANENT_COOKIE_NAME]);
		}

		self::start(true);
		session_destroy();
		$_SESSION = [];

		setcookie(self::SESSION_COOKIE_NAME, null, -1, $url['path'], $url['host'], false, true);

		unset($_COOKIE[self::SESSION_COOKIE_NAME]);
	
		return true;
	}


	public function populateUserData()
	{
		$db = DB::getInstance();
		$this->user = $db->first('SELECT * FROM membres WHERE id = ?;', (int)$this->id);

		if (!$this->user)
		{
			throw new \LogicException(sprintf('Aucun utilisateur trouvé avec l\'ID %s', var_export($this->id, true)));
		}

		$this->user->droits = new \stdClass;

		// Récupérer les droits
		$droits = $db->first('SELECT * FROM membres_categories WHERE id = ?;', (int)$this->user->id_categorie);

		foreach ($droits as $key=>$value)
		{
			// Renommer pour simplifier
			$key = str_replace('droit_', '', $key, $found);

			// Si le nom de colonne contient droit_ c'est que c'est un droit !
			if ($found)
			{
				$this->user->droits->$key = (int) $value;
			}
		}

		self::start(true);
		$_SESSION['user'] =& $this->user;

		return $this->user;
	}

	public function editUser($data)
	{
		return (new \Garradin\Membres)->edit($this->id, $data, false);
	}

	public function getUser($key = null)
	{
		if (null === $key)
		{
			return $this->user;
		}
		elseif (property_exists($key, $this->user))
		{
			return $this->user->$key;
		}
		else
		{
			return null;
		}
	}

	public function canAccess($category, $permission)
	{
		if (!$this->user)
		{
			return false;
		}

		return ($this->user->droits->$category >= $permission);
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
		$out['url'] = Security_OTP::getOTPAuthURL(Config::getInstance()->get('nom_asso'), $out['secret']);
	
		$qrcode = new QRCode($out['url']);
		$out['qrcode'] = 'data:image/svg+xml;base64,' . base64_encode($qrcode->toSVG());

		return $out;
	}

	public function sessionStore($key, $value)
	{
		if (!isset($_SESSION['storage']))
		{
			$_SESSION['storage'] = [];
		}

		if ($value === null)
		{
			unset($_SESSION['storage'][$key]);
		}
		else
		{
			$_SESSION['storage'][$key] = $value;
		}

		return true;
	}

	public function sessionGet($key)
	{
		if (!isset($_SESSION['storage'][$key]))
		{
			return null;
		}

		return $_SESSION['storage'][$key];
	}

	public function updateSessionData($membre = null, $droits = null)
	{
		if (is_null($membre))
		{
			$membre = $this->get($_SESSION['logged_user']['id']);
		}
		elseif (is_int($membre))
		{
			$membre = $this->get($membre);
		}

		if (is_null($droits))
		{
			$droits = $this->getDroits($membre['id_categorie']);
		}

		$membre['droits'] = $droits;
		$_SESSION['logged_user'] = $membre;
		return true;
	}

	public function sendMessage($dest, $sujet, $message, $copie = false)
	{
		$from = $this->getLoggedUser();
		$from = $from['email'];
		// Uniquement adresse email pour le moment car faudrait trouver comment
		// indiquer le nom mais qu'il soit correctement échappé FIXME

		$config = Config::getInstance();

		$message .= "\n\n--\nCe message a été envoyé par un membre de ".$config->get('nom_asso');
		$message .= ", merci de contacter ".$config->get('email_asso')." en cas d'abus.";

		if ($copie)
		{
			Utils::mail($from, $sujet, $message);
		}

		return Utils::mail($dest, $sujet, $message, ['From' => $from]);
	}


	public function checkPassword($password)
	{
		return Membres::checkPassword($password, $this->user->passe);
	}

	public function editSecurity(Array $data = [])
	{
		$user = $this->getLoggedUser();

		if (!$user)
		{
			throw new \LogicException('Utilisateur non connecté.');
		}

		$allowed_fields = ['passe', 'clef_pgp', 'secret_otp'];

		foreach ($data as $key=>$value)
		{
			if (!in_array($key, $allowed_fields))
			{
				throw new \RuntimeException(sprintf('Le champ %s n\'est pas autorisé dans cette méthode.', $key));
			}
		}

		if (isset($data['passe']) && trim($data['passe']) !== '')
		{
			if (strlen($data['passe']) < 5)
			{
				throw new UserException('Le mot de passe doit faire au moins 5 caractères.');
			}

			$data['passe'] = $this->_hashPassword($data['passe']);
		}
		else
		{
			unset($data['passe']);
		}

		if (isset($data['clef_pgp']))
		{
			$data['clef_pgp'] = trim($data['clef_pgp']);

			if (!$this->getPGPFingerprint($data['clef_pgp']))
			{
				throw new UserException('Clé PGP invalide : impossible d\'extraire l\'empreinte.');
			}
		}

		DB::getInstance()->simpleUpdate('membres', $data, 'id = '.(int)$user['id']);
		$this->updateSessionData();

		return true;
	}

	public function getPGPFingerprint($key, $display = false)
	{
		if (!Security::canUseEncryption())
		{
			return false;
		}

		$fingerprint = Security::getEncryptionKeyFingerprint($key);

		if ($display && $fingerprint)
		{
			$fingerprint = str_split($fingerprint, 4);
			$fingerprint = implode(' ', $fingerprint);
		}

		return $fingerprint;
	}
}
