<?php

namespace Garradin\UserTemplate;

use KD2\Brindille;
use KD2\Brindille_Exception;
use KD2\ErrorManager;

use Garradin\Config;
use Garradin\DB;
use Garradin\Template;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Web\Skeleton;
use Garradin\Files\Files;
use Garradin\Entities\Files\File;

use const Garradin\{ROOT, WWW_URL};

class Functions
{
	const FUNCTIONS_LIST = [
		'include',
		'http',
		'dump',
		'error',
		'read',
		'save',
		'admin_header',
		'admin_footer',
		'signature_url',
	];

	static public function admin_header(array $params): string
	{
		$tpl = Template::getInstance();
		$tpl->assign($params);
		return $tpl->fetch('admin/_head.tpl');
	}

	static public function admin_footer(array $params): string
	{
		$tpl = Template::getInstance();
		$tpl->assign($params);
		return $tpl->fetch('admin/_foot.tpl');
	}

	static public function save(array $params, Brindille $tpl): void
	{
		$id = Utils::basename(Utils::dirname($tpl->_tpl_path));

		if (!$id) {
			throw new Brindille_Exception('Unique document name could not be found');
		}

		if (empty($params['key'])) {
			throw new Brindille_Exception('Saving key is empty but is mandatory');
		}

		$key = $params['key'];
		unset($params['key']);
		$params = json_encode($params);

		$db = DB::getInstance();
		$db->preparedQuery('REPLACE INTO documents_data (document, key, value) VALUES (?, ?, ?);', $id, $key, $params);
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

	static public function error(array $params, Brindille $tpl)
	{
		throw new UserException($params['message']);
	}

	static public function read(array $params, UserTemplate $ut, int $line): string
	{
		if (empty($params['file'])) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "file" manquant pour la fonction "include"', $line));
		}

		if (strpos($params['file'], '..') !== false) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "file" invalide', $line));
		}

		if (substr($params['file'], 0, 2) == './') {
			$params['file'] = Utils::dirname($ut->_tpl_path) . substr($params['file'], 1);
		}

		$file = Files::get(File::CONTEXT_SKELETON . '/' . $params['file']);

		if ($file) {
			$content = $file->fetch();
		}
		else {
			$content = file_get_contents(ROOT . '/skel-dist/' . $params['file']);
		}

		if (!empty($params['base64'])) {
			return base64_encode($content);
		}

		return $content;
	}

	static public function signature_url(): string
	{
		$file = Config::getInstance()->file('signature');

		if (!$file) {
			return '';
		}

		return 'data:image/png;base64,' . base64_encode($file->fetch());
	}

	static public function include(array $params, UserTemplate $ut, int $line): void
	{
		if (empty($params['file'])) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "file" manquant pour la fonction "include"', $line));
		}

		if (strpos($params['file'], '..') !== false) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "file" invalide', $line));
		}

		if (substr($params['file'], 0, 1) == './') {
			$params['file'] = Utils::dirname($ut->_tpl_path) . substr($params['file'], 1);
		}

		// Avoid recursive loops
		$from = $ut->get('included_from') ?? [];

		if (in_array($params['file'], $from)) {
			throw new Brindille_Exception(sprintf('Ligne %d : boucle infinie d\'inclusion détectée : %s', $line, $params['file']));
		}

		try {
			$include = new UserTemplate($params['file']);
		}
		catch (\InvalidArgumentException $e) {
			throw new Brindille_Exception(sprintf('Ligne %d : fonction "include" : le fichier à inclure "%s" n\'existe pas', $line, $params['file']));
		}

		$params['included_from'] = array_merge($from, [$params['file']]);

		$include->assignArray($params);
		$include->display();
	}

	static public function http(array $params): void
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
		}
		else {
			throw new Brindille_Exception('No valid parameter found for http function');
		}
	}
}
