<?php

namespace Garradin\Membres;

use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\Membres;
use Garradin\UserException;

use const Garradin\SECRET_KEY;
use const Garradin\WWW_URL;

use KD2\Security;
use KD2\Security_OTP;
use KD2\QRCode;

class Session extends \KD2\UserSession
{
	// Personalisation de la config de UserSession
	protected $cookie_name = 'gdin';
	protected $remember_me_cookie_name = 'gdinp';
	protected $remember_me_expiry = '+3 months';

	// Extension des méthodes de UserSession
	public function __construct()
	{
		$url = parse_url(WWW_URL);

		parent::__construct(DB::getInstance(), [
			'cookie_domain' => $url['host'],
			'cookie_path'   => $url['path'],
			'cookie_secure' => (\Garradin\PREFER_HTTPS >= 2) ? true : false,
		]);
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
		return $this->db->first('SELECT m.*, c.droit_connexion, c.droit_wiki, 
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
			id_membre AS user_id, m.passe AS user_password, expire AS expiry
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
	public function isLogged()
	{
		if (empty($_SESSION['user']) && defined('\Garradin\LOCAL_LOGIN')
			&& is_int(\Garradin\LOCAL_LOGIN) && \Garradin\LOCAL_LOGIN > 0)
		{
			$this->create(\Garradin\LOCAL_LOGIN);
		}

		return parent::isLogged();
	}

	// Ici checkOTP utilise NTP en second recours
	public function checkOTP($secret, $code)
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

	static public function recoverPasswordCheck($id)
	{
		$db = DB::getInstance();
		$config = Config::getInstance();

		$champ_id = $config->get('champ_identifiant');

		$membre = $db->first('SELECT id, email, passe, clef_pgp FROM membres WHERE '.$champ_id.' = ? LIMIT 1;', trim($id));

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
		$message.= WWW_URL . 'admin/password.php?c=' . $query;
		$message.= "\n\nSi vous n'avez pas demandé à recevoir ce message, ignorez-le, votre mot de passe restera inchangé.";

		Utils::mail($membre->email, '['.$config->get('nom_asso').'] Mot de passe perdu ?', $message, [], $membre->clef_pgp);
		return true;
	}

	static public function recoverPasswordConfirm($code)
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

		$password = Utils::suggestPassword();

		$message = "Bonjour,\n\nVous avez demandé un nouveau mot de passe pour votre compte.\n\n";
		$message.= "Votre adresse email : ".$membre->email."\n";
		$message.= "Votre nouveau mot de passe : ".$password."\n\n";
		$message.= "Si vous n'avez pas demandé à recevoir ce message, merci de nous le signaler.";

		$password = Membres::hashPassword($password);

		$db->update('membres', ['passe' => $password], 'id = :id', ['id' => (int)$id]);

		return Utils::mail($membre->email, '['.$config->get('nom_asso').'] Nouveau mot de passe', $message, [], $membre->clef_pgp);
	}

	public function editUser($data)
	{
		(new Membres)->edit($this->user->id, $data, false);
		$this->refresh();

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
		$from = $this->getUser();
		$from = $from->email;
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
		$db->update('membres', $data, $db->where('id', (int)$this->user->id));
		$this->refresh();

		return true;
	}
}
