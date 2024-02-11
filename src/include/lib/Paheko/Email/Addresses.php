<?php

namespace Paheko\Email;

use Paheko\Config;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Plugins;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\Entities\Email\Address;
use Paheko\Entities\Files\File;
use Paheko\Entities\Users\User;
use Paheko\Users\DynamicFields;
use Paheko\UserTemplate\UserTemplate;
use Paheko\Web\Render\Render;

use Paheko\Files\Files;

use KD2\Mail_Message;
use KD2\SMTP;
use KD2\DB\EntityManager as EM;

class Addresses
{
	/**
	 * Antispam services that require to do a manual action to accept emails
	 */
	const BLACKLIST_MANUAL_VALIDATION_MX = '/mailinblack\.com|spamenmoins\.com/';

	const COMMON_DOMAINS = ['laposte.net', 'gmail.com', 'hotmail.fr', 'hotmail.com', 'wanadoo.fr', 'free.fr', 'sfr.fr', 'yahoo.fr', 'orange.fr', 'live.fr', 'outlook.fr', 'yahoo.com', 'neuf.fr', 'outlook.com', 'icloud.com', 'riseup.net', 'vivaldi.net', 'aol.com', 'gmx.de', 'lilo.org', 'mailo.com', 'protonmail.com', 'proton.me'];

	/**
	 * Return NULL if address is valid, or a string for an error message if invalid
	 */
	static public function checkForErrors(string $email, bool $mx_check = true): ?string
	{
		if (trim($email) === '') {
			return 'Adresse e-mail vide';
		}

		$local_part = null;
		$host = null;
		$email = self::normalize($email, $local_part, $host);

		if (!$email) {
			return 'Adresse e-mail invalide : vérifiez que vous n\'avez pas fait une faute de frappe.';
		}

		// Ce domaine n'existe pas (MX inexistant), erreur de saisie courante
		if ($host == 'gmail.fr') {
			return 'Adresse invalide : "gmail.fr" n\'existe pas, il faut utiliser "gmail.com"';
		}

		if (preg_match('![/@]!', $local_part)) {
			return 'Adresse e-mail invalide : vérifiez que vous n\'avez pas fait une faute de frappe.';
		}

		if (!SMTP::checkEmailIsValid($email, false)) {
			if (!trim($host)) {
				return 'Adresse e-mail invalide : vérifiez que vous n\'avez pas fait une faute de frappe.';
			}

			foreach (self::COMMON_DOMAINS as $common_domain) {
				similar_text($common_domain, $host, $percent);

				if ($percent > 90) {
					return sprintf('Adresse e-mail invalide : avez-vous fait une erreur, par exemple "%s" à la place de "%s" ?', $host, $common_domain);
				}
			}

			return 'Adresse e-mail invalide : vérifiez que vous n\'avez pas fait une faute de frappe.';
		}

		// Windows does not support MX lookups
		if (PHP_OS_FAMILY == 'Windows' || !$mx_check) {
			return null;
		}

		getmxrr($host, $mx_list);

		if (empty($mx_list)) {
			return 'Adresse e-mail invalide (le domaine indiqué n\'a pas de service e-mail) : vérifiez que vous n\'avez pas fait une faute de frappe.';
		}

		foreach ($mx_list as $mx) {
  			if (preg_match(self::BLACKLIST_MANUAL_VALIDATION_MX, $mx)) {
				return 'Adresse e-mail invalide : impossible d\'envoyer des mails à un service (de type mailinblack ou spamenmoins) qui demande une validation manuelle de l\'expéditeur. Merci de choisir une autre adresse e-mail.';
			}
		}

		return null;
	}

	static public function isValid(string $address, bool $check_mx = true): bool
	{
		return self::checkForErrors($address, $check_mx) === null;
	}

	static public function validate(string $address, bool $check_mx = true): void
	{
		$error = self::checkForErrors($address);

		if (null !== $error) {
			throw new UserException($error);
		}
	}

	static public function normalize(string $address, ?string &$local_part = null, ?string &$host = null): ?string
	{
		$address = strtolower(trim($address));

		$pos = strrpos($address, '@');

		if (!$pos) {
			return null;
		}

		$local_part = substr($address, 0, $pos);
		$host = substr($address, $pos + 1);
		$host = idn_to_ascii($host);

		$address = $local_part . '@' . $host;
		return $address;
	}

	/**
	 * Normalize email address and create a hash from this
	 */
	static public function hash(string $address): string
	{
		$address = self::normalize($address);
		return sha1($address);
	}

	/**
	 * Return an Email entity from the optout code
	 */
	static public function getFromOptout(string $code): ?Address
	{
		$hash = base64_decode(str_pad(strtr($code, '-_', '+/'), strlen($code) % 4, '=', STR_PAD_RIGHT));

		if (!$hash) {
			return null;
		}

		$hash = bin2hex($hash);
		return EM::findOne(Address::class, 'SELECT * FROM @TABLE WHERE hash = ?;', $hash);
	}

	/**
	 * Sets the address as invalid (no email can be sent to this address ever)
	 */
	static public function markAddressAsInvalid(string $address): void
	{
		$e = self::get($address);

		if (!$e) {
			return;
		}

		$e->set('invalid', true);
		$e->set('optout', false);
		$e->set('verified', false);
		$e->save();
	}

	/**
	 * Return an Email entity from an email address
	 */
	static public function get(string $address): ?Address
	{
		return EM::findOne(Address::class, 'SELECT * FROM @TABLE WHERE hash = ?;', self::hash($address));
	}

	/**
	 * Return an Email entity from an ID
	 */
	static public function getByID(int $id): ?Address
	{
		return EM::findOne(Address::class, 'SELECT * FROM @TABLE WHERE id = ?;', $id);
	}

	/**
	 * Return or create a new email entity
	 */
	static public function getOrCreate(string $address): Address
	{
		$e = self::get($address);
		$e ??= self::create($address);
		return $e;
	}

	static public function create(string $address): Address
	{
		$e = new Address;
		$e->setAddress($address);
		$e->save();
		return $e;
	}

	static public function listRejectedUsers(): DynamicList
	{
		$db = DB::getInstance();
		$email_field = 'u.' . $db->quoteIdentifier(DynamicFields::getFirstEmailField());

		$columns = [
			'id' => [
				'select' => 'a.id',
			],
			'identity' => [
				'label' => 'Membre',
				'select' => DynamicFields::getNameFieldsSQL('u'),
			],
			'email' => [
				'label' => 'Adresse',
				'select' => $email_field,
			],
			'user_id' => [
				'select' => 'u.id',
			],
			'hash' => [
			],
			'status' => [
				'label' => 'Statut',
			],
			'sent_count' => [
				'label' => 'Messages envoyés',
			],
			'last_sent' => [
				'label' => 'Dernière tentative d\'envoi',
			],
			'optout' => [],
			'fail_count' => [],
		];

		$tables = sprintf('emails_addresses a INNER JOIN users u ON %s IS NOT NULL AND %1$s != \'\' AND a.hash = email_hash(%1$s)', $email_field);

		$conditions = 'a.status < 0';

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('last_sent', true);
		$list->setModifier(function (&$row) {
			$row->last_sent = $row->last_sent ? new \DateTime($row->last_sent) : null;
		});
		return $list;
	}

	/**
	 * Handle a bounce message
	 * @param  string $raw_message Raw MIME message from SMTP
	 */
	static public function handleBounce(string $raw_message): ?array
	{
		$message = new Mail_Message;
		$message->parse($raw_message);

		$return = $message->identifyBounce();
		$address = $return['recipient'] ?? null;

		$signal = Plugins::fire('email.bounce', false, compact('address', 'message', 'return', 'raw_message'));

		if ($signal && $signal->isStopped()) {
			return null;
		}

		if (!$return) {
			return null;
		}

		if ($return['type'] == 'autoreply') {
			// Ignore auto-responders
			return $return;
		}
		elseif ($return['type'] == 'genuine') {
			// Forward emails that are not automatic to the organization email
			$config = Config::getInstance();

			$new = new Mail_Message;
			$new->setHeaders([
				'To'      => $config->org_email,
				'Subject' => 'Réponse à un message que vous avez envoyé',
			]);

			$new->setBody('Veuillez trouver ci-joint une réponse à un message que vous avez envoyé à un de vos membre.');

			$new->attachMessage($message->output());

			self::sendMessage(self::CONTEXT_SYSTEM, $new);
			return $return;
		}

		return self::handleManualBounce($return['recipient'], $return['type'], $return['message']);
	}

	static public function handleManualBounce(string $address, string $type, ?string $message): ?array
	{
		$return = compact('address', 'type', 'message');
		$email = self::getOrCreate($address);

		if (!$email) {
			return null;
		}

		$email->hasFailed($return);
		Plugins::fire('email.bounce.save.before', false, compact('email', 'address', 'return', 'type', 'message'));
		$email->save();

		return $return;
	}


	static public function getFromHeader(string $name = null, string $email = null): string
	{
		$config = Config::getInstance();

		if (null === $name) {
			$name = $config->org_name;
		}
		if (null === $email) {
			$email = $config->org_email;
		}

		$name = str_replace('"', '\\"', $name);
		$name = str_replace(',', '', $name); // Remove commas

		return sprintf('"%s" <%s>', $name, $email);
	}

	static public function getVerificationLimitDate(): \DateTime
	{
		$delay = Address::RESEND_VERIFICATION_DELAY . ' hours ago';
		return new \DateTime($delay);
	}
}
