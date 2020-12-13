<?php

namespace Garradin\Membres;

use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\Membres;
use Garradin\UserException;
use Garradin\Plugin;

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
	const SECTION_USERS = 'membres';
	const SECTION_ACCOUNTING = 'compta';

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
			'cookie_secure' => (\Garradin\PREFER_HTTPS >= 2) ? true : false,
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
			throw new UserException('Ce mot de passe figure dans une liste de mots de passe compromis. Si vous l\'avez utilisé sur d\'autres sites il est recommandé de le changer sur ces autres sites également.');
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
		$champ_id = Config::getInstance()->get('champ_identifiant');

		// Ne renvoie un membre que si celui-ci a le droit de se connecter
		$query = 'SELECT m.id, m.%1$s AS login, m.passe AS password, m.secret_otp AS otp_secret
			FROM membres AS m
			INNER JOIN membres_categories AS mc ON mc.id = m.id_categorie
			WHERE m.%1$s = ? COLLATE NOCASE AND mc.droit_connexion >= %2$d
			LIMIT 1;';

		$query = sprintf($query, $champ_id, Membres::DROIT_ACCES);

		return $this->db->first($query, $login);
	}

	protected function getUserDataForSession($id)
	{
		// Mettre à jour la date de connexion
		$this->db->preparedQuery('UPDATE membres SET date_connexion = datetime() WHERE id = ?;', [$id]);
		$config = Config::getInstance();

		return $this->db->first('SELECT m.*, m.'.$config->get('champ_identite').' AS identite,
			c.droit_connexion, c.droit_web, c.droit_documents,
			c.droit_membres, c.droit_compta, c.droit_config, c.droit_membres
			FROM membres AS m
			INNER JOIN membres_categories AS c ON m.id_categorie = c.id
			WHERE m.id = ? LIMIT 1;', $id);
	}

	protected function storeRememberMeSelector($selector, $hash, $expiry, $user_id)
	{
		return $this->db->insert('membres_sessions', [
			'selecteur' => $selector,
			'hash'      => $hash,
			'expire'    => $expiry,
			'id_membre' => $user_id,
		]);
	}

	protected function expireRememberMeSelectors()
	{
		return $this->db->delete('membres_sessions', $this->db->where('expire', '<', time()));
	}

	protected function getRememberMeSelector($selector)
	{
		return $this->db->first('SELECT selecteur AS selector, hash,
			s.id_membre AS user_id, m.passe AS user_password, expire AS expiry
			FROM membres_sessions AS s
			INNER JOIN membres AS m ON m.id = s.id_membre
			WHERE s.selecteur = ? LIMIT 1;', $selector);
	}

	protected function deleteRememberMeSelector($selector)
	{
		return $this->db->delete('membres_sessions', $this->db->where('selecteur', $selector));
	}

	protected function deleteAllRememberMeSelectors($user_id)
	{
		return $this->db->delete('membres_sessions', $this->db->where('id_membre', $user_id));
	}

	// Ajout de la gestion de LOCAL_LOGIN
	public function isLogged($disable_local_login = false)
	{
		$logged = parent::isLogged();

		if (!$disable_local_login && defined('\Garradin\LOCAL_LOGIN'))
		{
			$login_id = \Garradin\LOCAL_LOGIN;

			// On va chercher le premier membre avec le droit de gérer la config
			if (-1 === $login_id) {
				$login_id = $this->db->firstColumn('SELECT id FROM membres
					WHERE id_categorie IN (SELECT id FROM membres_categories WHERE droit_config = ?)
					LIMIT 1', Membres::DROIT_ADMIN);
			}

			if ($login_id > 0 && (!$logged || ($logged && $this->user->id != $login_id)))
			{
				$logged = $this->create($login_id);
			}
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
		$out['url'] = Security_OTP::getOTPAuthURL(Config::getInstance()->get('nom_asso'), $secret);

		$qrcode = new QRCode($out['url']);
		$out['qrcode'] = 'data:image/svg+xml;base64,' . base64_encode($qrcode->toSVG());

		return $out;
	}

	public function recoverPasswordSend($id)
	{
		$db = DB::getInstance();
		$config = Config::getInstance();

		$champ_id = $config->get('champ_identifiant');

		$membre = $db->first('SELECT id, email, passe, clef_pgp FROM membres WHERE '.$champ_id.' = ? COLLATE NOCASE LIMIT 1;', trim($id));

		if (!$membre || trim($membre->email) == '')
		{
			return false;
		}

		// valide pour 1 heure minimum
		$expire = ceil((time() - strtotime('2017-01-01')) / 3600) + 1;

		$hash = hash_hmac('sha256', $membre->email . $membre->id . $membre->passe . $expire, SECRET_KEY, true);
		$hash = substr(Security::base64_encode_url_safe($hash), 0, 16);

		$id = base_convert($membre->id, 10, 36);
		$expire = base_convert($expire, 10, 36);

		$query = sprintf('%s.%s.%s', $id, $expire, $hash);

		$message = "Bonjour,\n\nVous avez oublié votre mot de passe ? Pas de panique !\n\n";
		$message.= "Il vous suffit de cliquer sur le lien ci-dessous pour recevoir un nouveau mot de passe.\n\n";
		$message.= ADMIN_URL . 'password.php?c=' . $query;
		$message.= "\n\nSi vous n'avez pas demandé à recevoir ce message, ignorez-le, votre mot de passe restera inchangé.";

		return Utils::sendEmail(Utils::EMAIL_CONTEXT_SYSTEM, $membre->email, 'Mot de passe perdu ?', $message, $membre->id, $membre->clef_pgp);
	}

	public function recoverPasswordCheck($code, &$membre = null)
	{
		if (substr_count($code, '.') !== 2)
		{
			return false;
		}

		list($id, $expire, $email_hash) = explode('.', $code);

		$config = Config::getInstance();
		$db = DB::getInstance();

		$id = base_convert($id, 36, 10);
		$expire = base_convert($expire, 36, 10);

		$expire_timestamp = ($expire * 3600) + strtotime('2017-01-01');

		if (time() / 3600 > $expire_timestamp)
		{
			return false;
		}

		$membre = $db->first('SELECT id, email, passe, clef_pgp FROM membres WHERE id = ? LIMIT 1;', (int)$id);

		if (!$membre || trim($membre->email) == '')
		{
			return false;
		}

		$hash = hash_hmac('sha256', $membre->email . $membre->id . $membre->passe . $expire, SECRET_KEY, true);
		$hash = substr(Security::base64_encode_url_safe($hash), 0, 16);

		if (!hash_equals($hash, $email_hash))
		{
			return false;
		}

		return true;
	}

	public function recoverPasswordChange($code, $password, $password_confirm)
	{
		if (!$this->recoverPasswordCheck($code, $membre))
		{
			throw new UserException('Le code permettant de changer le mot de passe a expiré. Merci de bien vouloir recommencer la procédure.');
		}

		$password = trim($password);
		$password_confirm = trim($password_confirm);

		if (!hash_equals($password, $password_confirm))
		{
			throw new UserException('Le mot de passe et sa vérification ne sont pas identiques.');
		}

		self::checkPasswordValidity($password);

		$password = self::hashPassword($password);

		$message = "Bonjour,\n\nLe mot de passe de votre compte a bien été modifié.\n\n";
		$message.= "Votre adresse email : ".$membre->email."\n";
		$message.= "La demande émanait de l'adresse IP : ".Utils::getIP()."\n\n";
		$message.= "Si vous n'avez pas demandé à changer votre mot de passe, merci de nous le signaler.";

		DB::getInstance()->update('membres', ['passe' => $password], 'id = :id', ['id' => (int)$membre->id]);

		return Utils::sendEmail(Utils::EMAIL_CONTEXT_SYSTEM, $membre->email, 'Mot de passe changé', $message, $membre->id, $membre->clef_pgp);
	}

	public function editUser($data)
	{
		(new Membres)->edit($this->user->id, $data, false);
		$this->refresh(false);

		return true;
	}

	public function canAccess($category, $permission)
	{
		if (!$this->user)
		{
			return false;
		}

		return ($this->user->{'droit_' . $category} >= $permission);
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

	public function sendMessage($dest, $sujet, $message, $copie = false)
	{
		$user = $this->getUser();

		$content = "Ce message vous a été envoyé par :\n";
		$content.= sprintf("%s\n%s\n\n", $user->identite, $user->email);
		$content.= str_repeat('=', 70) . "\n\n";
		$content.= $message;

		if ($copie)
		{
			Utils::sendEmail(Utils::EMAIL_CONTEXT_PRIVATE, $user->email, $sujet, $content, $user->id);
		}

		return Utils::sendEmail(Utils::EMAIL_CONTEXT_PRIVATE, $dest, $sujet, $content);
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
			$data['passe'] = trim($data['passe']);

			self::checkPasswordValidity($data['passe']);

			$data['passe'] = self::hashPassword($data['passe']);
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
		$db->update('membres', $data, $db->where('id', (int)$this->user->id));
		$this->refresh(false);

		return true;
	}
}
