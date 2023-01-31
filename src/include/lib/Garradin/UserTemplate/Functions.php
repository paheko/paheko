<?php

namespace Garradin\UserTemplate;

use KD2\Brindille;
use KD2\Brindille_Exception;
use KD2\ErrorManager;
use KD2\JSONSchema;

use Garradin\Config;
use Garradin\DB;
use Garradin\Template;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Email\Emails;
use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\Entities\Module;

use const Garradin\{ROOT, WWW_URL};

class Functions
{
	const FUNCTIONS_LIST = [
		'include',
		'http',
		'debug',
		'error',
		'read',
		'save',
		'admin_header',
		'admin_footer',
		'signature',
		'mail',
	];

	static public function admin_header(array $params): string
	{
		$tpl = Template::getInstance();
		$tpl->assign($params);
		return $tpl->fetch('_head.tpl');
	}

	static public function admin_footer(array $params): string
	{
		$tpl = Template::getInstance();
		$tpl->assign($params);
		return $tpl->fetch('_foot.tpl');
	}

	static public function save(array $params, Brindille $tpl, int $line): void
	{
		$name = Utils::basename(Utils::dirname($tpl->_tpl_path));

		if (!$name) {
			throw new Brindille_Exception('Module name could not be found');
		}

		$table = 'module_data_' . $name;

		if (!empty($params['key'])) {
			if ($params['key'] == 'uuid') {
				$params['key'] = Utils::uuid();
			}

			$field = 'key';
			$where_value = $params['key'];
		}
		elseif (!empty($params['id'])) {
			$field = 'id';
			$where_value = $params['id'];
		}
		else {
			throw new Brindille_Exception('Aucun paramètre "id" ou "key" n\'a été renseigné');
		}

		$key = $params['key'] ?? null;
		$id = $params['id'] ?? null;

		unset($params['key'], $params['id']);

		$validate = null;

		if (isset($params['validate_schema'])) {
			$validate = $params['validate_schema'];
			unset($params['validate_schema']);
		}

		$db = DB::getInstance();

		if ($key == 'config') {
			$result = $db->firstColumn(sprintf('SELECT config FROM %s WHERE name = ?;', Module::TABLE), $name);
		}
		else {
			$db->exec(sprintf('
				CREATE TABLE IF NOT EXISTS %s (
					id INTEGER NOT NULL PRIMARY KEY,
					key TEXT NULL,
					document TEXT NOT NULL
				);
				CREATE UNIQUE INDEX IF NOT EXISTS %1$s_key ON %1$s (key);', $table));

			$result = $db->firstColumn(sprintf('SELECT document FROM %s WHERE %s;', $table, ($field . ' = ?')), $where_value);
		}

		// Merge before update
		if ($result) {
			$result = json_decode((string) $result, true);
			$params = array_merge($result, $params);
		}

		// Remove NULL values
		$params = array_filter($params, fn($a) => !is_null($a));

		if ($validate) {
			$schema = self::read(['file' => $validate], $tpl, $line);

			try {
				$s = JSONSchema::fromString($schema);
				$s->validate($params);
			}
			catch (\RuntimeException $e) {
				throw new Brindille_Exception(sprintf("ligne %d: impossible de valider le schéma:\n%s\n\n%s",
					$line, $e->getMessage(), json_encode($params, JSON_PRETTY_PRINT)));
			}
		}

		$value = json_encode($params);

		if ($key == 'config') {
			$db->update(Module::TABLE, ['config' => $value], 'name = :name', compact('name'));
			return;
		}

		if (!$result) {
			$db->insert($table, compact('document', 'key'));
		}
		else {
			$db->update($table, compact('document'), sprintf('%s = :match', $field), ['match' => $where_value]);
		}
	}

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

		Emails::queue(Emails::CONTEXT_PRIVATE, [$params['to']], null, $params['subject'], $params['body']);
	}

	static public function debug(array $params, Brindille $tpl)
	{
		if (!count($params)) {
			$params = $tpl->getAllVariables();
		}

		$dump = htmlspecialchars(ErrorManager::dump($params));

		// FIXME: only send back HTML when content-type is text/html, or send raw text
		$out = sprintf('<pre style="background: yellow; padding: 5px; overflow: auto">%s</pre>', $dump);

		if (!empty($params['stop'])) {
			echo $out; exit;
		}

		return $out;
	}

	static public function error(array $params, Brindille $tpl)
	{
		throw new UserException($params['message'] ?? 'Erreur du module');
	}

	static protected function getFilePath(array $params, string $arg_name, UserTemplate $ut, int $line)
	{
		if (empty($params[$arg_name])) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "%s" manquant', $arg_name, $line));
		}

		if (strpos($params[$arg_name], '..') !== false) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "%s" invalide', $line, $arg_name));
		}

		$path = $params[$arg_name];

		if (substr($path, 0, 2) == './') {
			$path = Utils::dirname($ut->_tpl_path) . substr($path, 1);
		}

		return $path;
	}

	static public function read(array $params, UserTemplate $ut, int $line): string
	{
		$path = self::getFilePath($params, 'file', $ut, $line);

		$file = Files::get(File::CONTEXT_SKELETON . '/' . $path);

		if ($file) {
			$content = $file->fetch();
		}
		else {
			$content = file_get_contents(ROOT . '/skel-dist/' . $path);
		}

		if (!empty($params['base64'])) {
			return base64_encode($content);
		}

		return $content;
	}

	static public function signature(): string
	{
		$file = Config::getInstance()->file('signature');

		if (!$file) {
			return '';
		}

		// We can't just use the image URL as it would not be accessible by PDF programs
		$url = 'data:image/png;base64,' . base64_encode($file->fetch());

		return sprintf('<figure class="signature"><img src="%s" alt="Signature" /></figure>', $url);
	}

	static public function include(array $params, UserTemplate $ut, int $line): void
	{
		$path = self::getFilePath($params, 'file', $ut, $line);

		// Avoid recursive loops
		$from = $ut->get('included_from') ?? [];

		if (in_array($path, $from)) {
			throw new Brindille_Exception(sprintf('Ligne %d : boucle infinie d\'inclusion détectée : %s', $line, $path));
		}

		try {
			$include = new UserTemplate($path);
		}
		catch (\InvalidArgumentException $e) {
			throw new Brindille_Exception(sprintf('Ligne %d : fonction "include" : le fichier à inclure "%s" n\'existe pas', $line, $path));
		}

		$params['included_from'] = array_merge($from, [$path]);

		$include->assignArray(array_merge($ut->getAllVariables(), $params));
		$include->display();

		if (isset($params['keep'])) {
			$keep = explode(',', $params['keep']);
			$keep = array_map('trim', $keep);

			foreach ($keep as $name) {
				// Transmit variables
				$ut::__assign(['var' => $name, 'value' => $include->get($name)], $ut);
			}
		}

		// Transmit nocache to parent template
		if ($include->get('nocache')) {
			$ut::__assign(['nocache' => true], $ut);
		}
	}

	static public function http(array $params, UserTemplate $tpl): void
	{
		if (headers_sent()) {
			return;
		}

		if (isset($params['redirect'])) {
			Utils::redirect($params['redirect']);
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

		if (!empty($params['type'])) {
			if ($params['type'] == 'pdf') {
				$params['type'] = 'application/pdf';
			}

			header('Content-Type: ' . $params['type'], true);
		}

		if (isset($params['download'])) {
			header(sprintf('Content-Disposition: attachment; filename="%s"', Utils::safeFileName($params['download'])), true);
		}
	}
}
