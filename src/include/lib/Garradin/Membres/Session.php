<?php

namespace Garradin\Membres;

use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\Membres;
use Garradin\UserException;

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
		if ($write || isset($_COOKIE[self::SESSION_COOKIE_NAME]))
		{
			session_name(self::SESSION_COOKIE_NAME);
			return session_start(self::getSessionOptions());
		}

		return false;
	}

	static public function refresh()
	{
		return self::start(true);
	}

	static public function get()
	{
		try {
			return new Session;
		}
		catch (\LogicException $e) {
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
		if ($membre->droit_connexion == Membres::DROIT_AUCUN)
		{
			return false;
		}

		if ($membre->secret_otp)
		{
			self::start(true);

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
			$user = self::createUserSession($membre->id);

			if ($permanent)
			{
				self::createPermanentSession($membre);
			}

			return true;
		}
	}

	static protected function createUserSession($id)
	{
		$db = DB::getInstance();
		$user = $db->first('SELECT * FROM membres WHERE id = ?;', (int)$id);

		if (!$user)
		{
			throw new \LogicException(sprintf('Aucun utilisateur trouvé avec l\'ID %s', $id));
		}

		$user->droits = new \stdClass;

		// Récupérer les droits
		$droits = $db->first('SELECT * FROM membres_categories WHERE id = ?;', (int)$user->id_categorie);

		foreach ($droits as $key=>$value)
		{
			// Renommer pour simplifier
			$key = str_replace('droit_', '', $key, $found);

			// Si le nom de colonne contient droit_ c'est que c'est un droit !
			if ($found)
			{
				$user->droits->$key = (int) $value;
			}
		}

		self::start(true);
		$_SESSION['user'] = $user;

		return $user;
	}

	/**
	 * Créer une session permanente "remember me"
	 *
	 * @see autoLogin method
	 * @param  \stdClass $user
	 * @return boolean
	 */
	static protected function createPermanentSession(\stdClass $user)
	{
		$selector = hash(self::HASH_ALGO, Security::random_bytes(10));
		$verifier = hash(self::HASH_ALGO, Security::random_bytes(10));
		$expire = (new \DateTime)->modify('+3 months');

		$hash = hash(self::HASH_ALGO, $selector . $verifier . $user->passe . $expire->format(DATE_ATOM));

		DB::getInstance()->insert('membres_sessions', [
			'selecteur' => $selector,
			'hash'      => $hash,
			'expire'    => $expire,
			'id_membre' => $user->id,
		]);

		$cookie = $selector . '|' . $verifier;

		$options = self::getSessionOptions();

		setcookie(self::PERMANENT_COOKIE_NAME, $cookie, $expire->getTimestamp(),
			$options['cookie_path'], $options['cookie_domain'], $options['cookie_secure'],
			$options['cookie_httponly']);

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

		if (!self::checkOTP($user->secret, $code))
		{
			var_dump($user->secret, $code);
			return false;
		}

		self::createUserSession($user->id);
		$session = new Session($user->id);

		return $session;
	}

	static public function checkOTP($secret, $code)
	{
		if (!Security_OTP::TOTP($secret, $code))
		{
			// Vérifier encore, mais avec le temps NTP
			// au cas où l'horloge du serveur n'est pas à l'heure
			$time = Security_OTP::getTimeFromNTP(\Garradin\NTP_SERVER);

			if (!Security_OTP::TOTP($secret, $code, $time))
			{
				return false;
			}
		}

		return true;
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

		$password = Membres::hashPassword($password);

		$db->update('membres', ['passe' => $password], 'id = :id', ['id' => (int)$id]);

		return Utils::mail($dest, '['.$config->get('nom_asso').'] Nouveau mot de passe', $message);
	}

	public function __construct()
	{
		if (empty($_SESSION['user']) && defined('\Garradin\LOCAL_LOGIN')
			&& is_int(\Garradin\LOCAL_LOGIN) && \Garradin\LOCAL_LOGIN > 0)
		{
			self::createUserSession(\Garradin\LOCAL_LOGIN);
		}

		// Démarrage session
		self::start();

		if (empty($_SESSION['user']))
		{
			$this->autoLogin();
		}

		if (empty($_SESSION['user']))
		{
			throw new \LogicException('Aucun utilisateur connecté.');
		}

		$this->user = $_SESSION['user'];
		$this->id = $this->user->id;
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
			'verifier' => $data[1],
		];
	}

	/**
	 * Connexion automatique en utilisant un cookie permanent
	 * (fonction "remember me")
	 *
	 * @link   https://www.databasesandlife.com/persistent-login/
	 * @link   https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
	 * @link   https://paragonie.com/blog/2017/02/split-tokens-token-based-authentication-protocols-without-side-channels
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

		// Suppression des sessions qui ont expiré déjà
		$db->delete('membres_sessions', 'expire < strftime(\'%s\',\'now\')');
		
		$row = $db->first('SELECT ms.hash, ms.id_membre AS id, 
			strftime("%s", ms.expire) AS expire, membres.passe
			FROM membres_sessions AS ms
			INNER JOIN membres ON membres.id = ms.id_membre
			WHERE ms.selecteur = ?;',
			$cookie->selector);

		// Le sélecteur n'est pas valide: supprimons le cookie
		if (!$row)
		{
			return $this->logout();
		}

		// La session stockée ne sert plus à rien à partir de maintenant,
		// et ça empêche de le rejouer
		$db->delete('membres_sessions', 'selecteur = ?', $cookie->selector);

		// On utilise le mot de passe: si l'utilisateur change de mot de passe
		// toutes les sessions précédentes sont invalidées
		$hash = hash(self::HASH_ALGO, $cookie->selector . $cookie->verifier . $row->passe . date(DATE_ATOM, $row->expire));

		// Vérification du token
		if (!hash_equals($row->hash, $hash))
		{
			// Le sélecteur est valide, mais pas le token ?
			// c'est probablement que le cookie a été volé, qu'un attaquant
			// a obtenu un nouveau token, et que l'utilisateur se représente 
			// avec un token qui n'est plus valide.
			// Dans ce cas supprimons toutes les sessions de ce membre pour 
			// le forcer à se re-connecter

			return $this->logout();
		}

		// Re-générons un nouveau vérifieur et mettons à jour le cookie
		// car chaque vérifieur est à usage unique
		self::createPermanentSession($row);

		$this->id = $row->id;

		self::createUserSession($this->id);

		return true;
	}

	public function logout()
	{
		$options = self::getSessionOptions();

		if ($cookie = $this->getPermanentCookie())
		{
			// Suppression de cette session permanente
			DB::getInstance()->delete('membres_sessions', 'selecteur = ?', $cookie->selector);

			setcookie(self::PERMANENT_COOKIE_NAME, null, -1, $options['cookie_path'],
				$options['cookie_domain'], $options['cookie_secure'], $options['cookie_httponly']);
			unset($_COOKIE[self::PERMANENT_COOKIE_NAME]);
		}

		self::start(true);
		session_destroy();
		$_SESSION = [];

		setcookie($options['name'], null, -1, $options['cookie_path'],
			$options['cookie_domain'], $options['cookie_secure'], $options['cookie_httponly']);

		unset($_COOKIE[self::SESSION_COOKIE_NAME]);
	
		return true;
	}

	public function editUser($data)
	{
		(new Membres)->edit($this->id, $data, false);
		$this->updateSessionData();

		return true;
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

	public function sendMessage($dest, $sujet, $message, $copie = false)
	{
		$from = $this->getUser();
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

			$data['passe'] = Membres::hashPassword(trim($data['passe']));
		}
		else
		{
			unset($data['passe']);
		}

		if (isset($data['clef_pgp']) && trim($data['clef_pgp']) !== '')
		{
			$data['clef_pgp'] = trim($data['clef_pgp']);

			if (!$this->getPGPFingerprint($data['clef_pgp']))
			{
				throw new UserException('Clé PGP invalide : impossible d\'extraire l\'empreinte.');
			}
		}

		$db = DB::getInstance();
		$db->update('membres', $data, $db->where('id', (int)$this->id));
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

	public function updateSessionData()
	{
		$this->user = self::createUserSession($this->id);
	}
}
