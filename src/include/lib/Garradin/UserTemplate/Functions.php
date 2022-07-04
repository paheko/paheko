<?php

namespace Garradin\UserTemplate;

use KD2\Brindille;
use KD2\Brindille_Exception;
use KD2\ErrorManager;

use Garradin\Utils;
use Garradin\Web\Skeleton;

use const Garradin\WWW_URL;

class Functions
{
	const FUNCTIONS_LIST = [
		'include',
		'http',
		'dump',
		'mail',
	];

	static public function mail(array $params, Brindille $tpl, int $line)
	{
		if (empty($params['to'])) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "to" manquant pour la fonction "mail"', $line));
		}

		if (empty($params['subject'])) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "subject" manquant pour la fonction "mail"', $line));
		}

		if (empty($params['body'])) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "body" manquant pour la fonction "mail"', $line));
		}

		Utils::sendEmail(Utils::EMAIL_CONTEXT_PRIVATE, $params['to'], $params['subject'], $params['body']);
	}

	static public function dump(array $params, Brindille $tpl)
	{
		if (!count($params)) {
			$params = $tpl->getAllVariables();
		}

		$dump = htmlspecialchars(ErrorManager::dump($params));

		// FIXME: only send back HTML when content-type is text/html, or send raw text
		return sprintf('<pre style="background: yellow; padding: 5px; overflow: auto">%s</pre>', $dump);
	}

	static public function include(array $params, UserTemplate $ut, int $line): void
	{
		if (empty($params['file'])) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "file" manquant pour la fonction "include"', $line));
		}

		// Avoid recursive loops
		$from = $ut->get('included_from') ?? [];

		if (in_array($params['file'], $from)) {
			throw new Brindille_Exception(sprintf('Ligne %d : boucle infinie d\'inclusion détectée : %s', $line, $params['file']));
		}

		$s = new Skeleton($params['file']);

		if (!$s->exists()) {
			throw new Brindille_Exception(sprintf('Ligne %d : fonction "include" : le fichier à inclure "%s" n\'existe pas', $line, $params['file']));
		}

		$params['included_from'] = array_merge($from, [$params['file']]);

		$s->display($params);
	}

	static public function http(array $params, UserTemplate $tpl): void
	{
		if (headers_sent()) {
			return;
		}

		if (isset($params['code'])) {
			static $codes = [
				100 => 'Continue',
				101 => 'Switching Protocols',
				102 => 'Processing',
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',
				207 => 'Multi-Status',
				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				306 => 'Switch Proxy',
				307 => 'Temporary Redirect',
				400 => 'Bad Request',
				401 => 'Unauthorized',
				402 => 'Payment Required',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				410 => 'Gone',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',
				418 => 'I\'m a teapot',
				422 => 'Unprocessable Entity',
				423 => 'Locked',
				424 => 'Failed Dependency',
				425 => 'Unordered Collection',
				426 => 'Upgrade Required',
				449 => 'Retry With',
				450 => 'Blocked by Windows Parental Controls',
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported',
				506 => 'Variant Also Negotiates',
				507 => 'Insufficient Storage',
				509 => 'Bandwidth Limit Exceeded',
				510 => 'Not Extended',
			];

			if (!isset($codes[$params['code']])) {
				throw new Brindille_Exception('Code HTTP inconnu');
			}

			header(sprintf('HTTP/1.1 %d %s', $params['code'], $codes[$params['code']]), true);
		}
		elseif (isset($params['redirect'])) {
			Utils::redirect($params['redirect']);
		}
		elseif (isset($params['type'])) {
			header('Content-Type: ' . $params['type'], true);
			$tpl->setContentType($params['type']);
		}
		elseif (isset($params['download'])) {
			header(sprintf('Content-Disposition: attachment; filename="%s"', Utils::safeFileName($params['download'])), true);
		}
		else {
			throw new Brindille_Exception('No valid parameter found for http function');
		}
	}
}
