<?php

namespace Garradin\Entities\Email;

use Garradin\Config;
use Garradin\CSV;
use Garradin\UserException;
use Garradin\Entities\Emails\Email;
use Garradin\Users\DynamicFields;
use Garradin\UserTemplate\UserTemplate;
use Garradin\Web\Render\Render;

class Mailing
{
	const RENDER_FORMATS = [
		null => 'Texte brut',
		Render::FORMAT_SKRIV => 'SkrivML',
		Render::FORMAT_MARKDOWN => 'MarkDown',
	];

	protected string $subject;
	protected string $body;
	protected ?string $render_format = null;

	/**
	 * Create a mass mailing
	 */
	static public function create(iterable $recipients, string $subject, string $message, bool $send_copy, ?string $render): \stdClass
	{
		$list = [];

		foreach ($recipients as $recipient) {
			if (empty($recipient->email)) {
				continue;
			}

			$list[$recipient->email] = $recipient;
		}

		if (!count($list)) {
			throw new UserException('La liste de destinataires sélectionnée ne comporte aucun membre, ou aucun avec une adresse e-mail renseignée.');
		}

		$html = null;
		$tpl = null;

		$random = array_rand($list);

		if (false !== strpos($message, '{{')) {
			$tpl = new UserTemplate;
			$tpl->setCode($message);
			$tpl->toggleSafeMode(true);
			$tpl->assignArray((array)$list[$random]);
			$tpl->setEscapeDefault(null);

			try {
				if (!$render) {
					// Disable HTML escaping for plaintext emails
					$message = $tpl->fetch();
				}
				else {
					$html = $tpl->fetch();
				}
			}
			catch (\KD2\Brindille_Exception $e) {
				throw new UserException('Erreur de syntaxe dans le corps du message :' . PHP_EOL . $e->getPrevious()->getMessage(), 0, $e);
			}
		}

		if ($render) {
			$html = Render::render($render, null, $html ?? $message);
		}
		elseif (null !== $html) {
			$html = '<pre>' . $html . '</pre>';
		}
		else {
			$html = '<pre>' . htmlspecialchars(wordwrap($message)) . '</pre>';
		}

		$recipients = $list;

		$config = Config::getInstance();
		$sender = sprintf('"%s" <%s>', $config->org_name, $config->org_email);
		$message = (object) compact('recipients', 'subject', 'message', 'sender', 'tpl', 'send_copy', 'render');
		$message->preview = (object) [
			'to'      => $random,
			// Not required to be a valid From header, this is just a preview
			'from'    => $sender,
			'subject' => $subject,
			'html'    => $html,
		];

		return $message;
	}

	/**
	 * Send a mass mailing
	 */
	static public function send(\stdClass $mailing): void
	{
		if (!isset($mailing->recipients, $mailing->subject, $mailing->message, $mailing->send_copy)) {
			throw new \InvalidArgumentException('Invalid $mailing object');
		}

		if (!count($mailing->recipients)) {
			throw new UserException('Aucun destinataire de la liste ne possède d\'adresse email.');
		}

		Emails::queue(Emails::CONTEXT_BULK,
			$mailing->recipients,
			null, // Default sender
			$mailing->subject,
			$mailing->tpl ?? $mailing->message,
			$mailing->render ?? null
		);

		if ($mailing->send_copy)
		{
			$config = Config::getInstance();
			Emails::queue(Emails::CONTEXT_BULK, [$config->org_email => null], null, $mailing->subject, $mailing->message);
		}
	}

	static public function export(string $format, \stdClass $mailing): void
	{
		$rows = $mailing->recipients;
		$id_field = DynamicFields::getNameFieldsSQL('u');

		foreach ($rows as $key => &$row) {
			$row = [$key, $row->$id_field ?? ''];
		}

		unset($row);

		CSV::export($format, 'Destinataires message collectif', $rows, ['Adresse e-mail', 'Identité']);
	}
}