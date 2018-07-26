<?php

namespace Garradin;

use KD2\Security;

class Email
{
	const STATUT_ATTENTE_ENVOI = 0;
	const STATUT_EN_COURS_ENVOI = 1;

	/**
	 * Valeur de blocage pour les emails qui ont demandé à ne plus recevoir de message
	 */
	const REJET_OPTOUT = -1;

	/**
	 * Valeur de blocage pour les emails qui sont revenus avec une erreur permanente
	 */
	const REJET_DEFINITIF = -2;

	/**
	 * Seuil à partir duquel on n'essaye plus d'envoyer de message à cette adresse
	 */
	const REJET_ABANDON = 3;

	/**
	 * Renvoie la liste des emails en attente d'envoi dans la queue,
	 * sauf ceux qui correspondent à des adresses bloquées
	 * @return array
	 */
	public function listQueue()
	{
		// Nettoyage de la queue déjà
		$this->purgeQueueFromRejected();
		return DB::getInstance()->get('SELECT * FROM emails_attente	WHERE statut = ?;', self::STATUT_ATTENTE_ENVOI);
	}

	/**
	 * Ajoute un message à la queue d'envoi
	 * @param  string $to        Destinataire
	 * @param  string $subject   Sujet du message
	 * @param  string $content   Contenu
	 * @param  integer $id_membre ID membre (facultatif)
	 * @param  string $pgp_key   Clé PGP, si renseigné le message sera chiffré à l'aide de cette clé
	 * @return boolean
	 */
	public function appendToQueue($to, $subject, $content, $id_membre = null, $pgp_key = null)
	{
		// Ne pas envoyer de mail à des adresses invalides
		if (!filter_var($to, FILTER_VALIDATE_EMAIL))
		{
			throw new UserException('Adresse email invalide: ' . $to);
		}

		if ($pgp_key)
		{
			$content = Security::encryptWithPublicKey($pgp_key, $content);
		}

		$content = wordwrap($content);
		$content = trim($content);

		return DB::getInstance()->insert('emails_attente', [
			'adresse'   => $to,
			'id_membre' => (int) $id_membre ?: null,
			'sujet'     => $subject,
			'contenu'   => $content,
		]);
	}

	/**
	 * Lance la queue d'envoi
	 * @return void
	 */
	public function runQueue()
	{
		$res = DB::getInstance()->iterate('SELECT * FROM emails_attente	WHERE statut = ?;', self::STATUT_ATTENTE_ENVOI);

		foreach ($res as $row)
		{
			$this->mail($row->adresse, $row->sujet, $row->message);
		}
	}

	/**
	 * Supprime de la queue les messages liés à des adresses invalides
	 * ou qui ne souhaitent plus recevoir de message
	 * @return boolean
	 */
	public function purgeQueueFromRejected()
	{
		return DB::getInstance()->delete('emails_attente',
			'adresse IN (SELECT adresse FROM emails_rejets WHERE r.statut < 0 OR r.statut > ?)',
			self::REJET_ABANDON);
	}

	/**
	 * Change le statut d'un message dans la queue d'envoi
	 * @param integer $id
	 * @param integer $status
	 * @return boolean
	 */
	public function setMessageStatusInQueue($id, $status)
	{
		if (!in_array($status, [self::STATUT_ATTENTE_ENVOI, self::STATUT_EN_COURS_ENVOI]))
		{
			throw new \UnexpectedValueException('Statut inconnu: ' . $status);
		}

		return DB::getInstance()->update('emails_attente', ['statut' => $status], 'id = ' . (int)$id);
	}

	/**
	 * Supprime un message de la queue d'envoi
	 * @param  integer $id
	 * @return boolean
	 */
	public function deleteFromQueue($id)
	{
		return DB::getInstance()->delete('emails_attente', 'id = ?', (int)$id);
	}

	/**
	 * Tente de trouver le statut de rejet (définitif ou temporaire) d'un message à partir du message d'erreur reçu
	 * @param  string $error_message
	 * @return integer|null
	 */
	public function guessRejectionStatus($error_message)
	{
		if (preg_match('/unavailable|doesn\'t\s*have|quota|does\s*not\s*exist|invalid|Unrouteable|unknown|illegal/i', $error_message))
		{
			return self::REJET_DEFINITIF;
		}
		elseif (preg_match('/rejete|rejected|spam\s*detected|Service\s*refus|greylist/i', $error_message))
		{
			return 1;
		}

		return null;
	}

	/**
	 * Met à jour le statut de rejet d'une adresse
	 * @param string $address
	 * @param integer $status
	 * @param string $message
	 * @return boolean
	 */
	public function setRejectedStatus($address, $status, $message)
	{
		$address = strtolower(trim($address));

		if (!filter_var($address, FILTER_VALIDATE_EMAIL))
		{
			return false;
		}

		if ($status < 0 && !in_array($status, [self::REJET_DEFINITIF, self::REJET_OPTOUT]))
		{
			throw new \UnexpectedValueException('Statut inconnu: ' . $status);
		}

		if ($status == 0)
		{
			throw new \UnexpectedValueException('Statut invalide');
		}

		return DB::getInstance()->preparedQuery('INSERT OR IGNORE INTO emails_rejets (adresse, message, statut) VALUES (?, ?, ?);',
			[$address, $message, $status]);
	}

	/**
	 * Vérifie qu'une adresse est valide
	 * @param  string $address
	 * @return boolean|integer FALSE si l'adresse est invalide (syntaxe) ou un entier si l'adresse a été rejetée
	 */
	static public function checkAddress($address)
	{
		$address = strtolower(trim($address));

		if (!filter_var($address, FILTER_VALIDATE_EMAIL))
		{
			return false;
		}

		// Ce domaine n'existe pas (MX inexistant), erreur de saisie courante
		if (substr($address, -10) == '@gmail.fr')
		{
			return false;
		}

		return DB::getInstance()->firstColumn('SELECT statut FROM emails_rejets WHERE adresse = ?;', $address);
	}

	protected function mail($to, $subject, $content, array $headers = [])
	{
		// Création du contenu du message
		$config = Config::getInstance();

		$subject = sprintf('[%s] %s', $config->get('nom_asso'), $subject);

		$unsubscribe_url = sprintf('%semail.php?optout=%s', ADMIN_URL, rawurlencode($to));

		$content .= sprintf("\n\n-- \n%s\n%s\n\n", $config->get('nom_asso'), $config('site_asso'));
		$content .= "Vous recevez ce message car vous êtes inscrit comme membre de l'association.\n";
		$content .= "Pour ne plus recevoir de message de notre part cliquez ici :\n" . $unsubscribe_url;

		$content = preg_replace("#(?<!\r)\n#si", "\r\n", $content);

		$headers['List-Unsubscribe'] = sprintf('<%s>', $unsubscribe_url);

		if (FORCE_EMAIL_FROM)
		{
			$headers['From'] = sprintf('"%s" <%s>', sprintf('=?UTF-8?B?%s?=', base64_encode($config->get('nom_asso'))), FORCE_EMAIL_FROM);
			$headers['Return-Path'] = FORCE_EMAIL_FROM;
			$headers['Reply-To'] = $config->get('email_asso');
		}
		else
		{
			$headers['From'] = sprintf('"%s" <%s>', sprintf('=?UTF-8?B?%s?=', base64_encode($config->get('nom_asso'))), $config->get('email_asso'));
			$headers['Return-Path'] = $config->get('email_asso');
		}

		$headers['MIME-Version'] = '1.0';
		$headers['Content-type'] = 'text/plain; charset=UTF-8';

		$hash = sha1(uniqid() . var_export([$headers, $to, $subject, $content], true));
		$headers['Message-ID'] = sprintf('%s@%s', $hash, isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : gethostname());

		if (SMTP_HOST)
		{
			$const = '\KD2\SMTP::' . strtoupper(SMTP_SECURITY);
			
			if (!defined($const))
			{
				throw new \LogicException('Configuration: SMTP_SECURITY n\'a pas une valeur reconnue. Valeurs acceptées: STARTTLS, TLS, SSL, NONE.');
			}

			$secure = constant($const);

			$smtp = new SMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, $secure);
			return $smtp->send($to, $subject, $content, $headers);
		}
		else
		{
			// Encodage du sujet
			$subject = sprintf('=?UTF-8?B?%s?=', base64_encode($subject));
			$raw_headers = '';

			// Sérialisation des entêtes
			foreach ($headers as $name=>$value)
			{
				$raw_headers .= sprintf("%s: %s\r\n", $name, $value);
			}

			return \mail($to, $subject, $content, $raw_headers);
		}
	}
}
