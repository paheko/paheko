<?php

namespace Garradin\UserTemplate;

use KD2\Brindille;
use KD2\Brindille_Exception;
use KD2\ErrorManager;
use KD2\JSONSchema;

use Garradin\Config;
use Garradin\DB;
use Garradin\Plugins;
use Garradin\Template;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Email\Emails;
use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\Entities\Module;
use Garradin\Entities\User\Email;
use Garradin\Users\Session;

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
		'captcha',
		'mail',
	];

	const COMPILE_FUNCTIONS_LIST = [
		':break' => [self::class, 'break'],
	];

	/**
	 * Compile function to break inside a loop
	 */
	static public function break(string $name, string $params, Brindille $tpl, int $line)
	{
		$in_loop = false;
		foreach ($tpl->_stack as $element) {
			if ($element[0] == $tpl::SECTION) {
				$in_loop = true;
				break;
			}
		}

		if (!$in_loop) {
			throw new Brindille_Exception(sprintf('Error on line %d: break can only be used inside a section', $line));
		}

		return '<?php break; ?>';
	}

	static public function admin_header(array $params): string
	{
		$tpl = Template::getInstance();
		$tpl->assign($params);
		$tpl->assign('plugins_menu', Plugins::listModulesAndPluginsMenu(Session::getInstance()));
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
		$name = strtok(Utils::dirname($tpl->_tpl_path), '/');

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
			$field = null;
		}

		$key = $params['key'] ?? null;
		$id = $params['id'] ?? null;
		$assign_new_id = $params['assign_new_id'] ?? null;
		$validate = $params['validate_schema'] ?? null;

		unset($params['key'], $params['id'], $params['assign_new_id'], $params['validate_schema']);

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

			if ($field) {
				$result = $db->firstColumn(sprintf('SELECT document FROM %s WHERE %s;', $table, ($field . ' = ?')), $where_value);
			}
			else {
				$result = null;
			}
		}

		// Merge before update
		if ($result) {
			$result = json_decode((string) $result, true);
			$params = array_merge($result, $params);
		}

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

		$document = $value;
		if (!$result) {
			$db->insert($table, compact('document', 'key'));

			if ($assign_new_id) {
				$tpl->assign($assign_new_id, $db->lastInsertId());
			}
		}
		else {
			$db->update($table, compact('document'), sprintf('%s = :match', $field), ['match' => $where_value]);
		}
	}

	static public function captcha(array $params, Brindille $tpl, int $line)
	{
		$secret = md5(SECRET_KEY . Utils::getSelfURL(false));

		if (isset($params['html'])) {
			$c = Security::createCaptcha($secret, $params['lang'] ?? 'fr');
			return sprintf('<label for="f_c_42">Merci d\'écrire <strong><q>%s</q></strong> en chiffres&nbsp;:</label>
				<input type="text" name="f_c_42" id="f_c_42" placeholder="Exemple : 1234" />
				<input type="hidden" name="f_c_43" value="%s" />',
				$c['spellout'], $c['hash']);
		}
		elseif (isset($params['assign_hash']) && isset($params['assign_number'])) {
			$c = Security::createCaptcha($secret, $params['lang'] ?? 'fr');
			$tpl->assign($params['assign_hash'], $c['hash']);
			$tpl->assign($params['assign_number'], $c['spellout']);
		}
		elseif (isset($params['verify'])) {
			$hash = $_POST['f_c_43'] ?? '';
			$number = $_POST['f_c_42'] ?? '';
		}
		elseif (array_key_exists('verify_number', $params)) {
			$hash = $params['verify_hash'] ?? '';
			$number = $params['verify_number'] ?? '';
		}
		else {
			throw new Brindille_Exception(sprintf('Line %d: no valid arguments supplied for "captcha" function', $line));
		}

		$error = 'Réponse invalide à la vérification anti-robot';

		if (!Security::checkCaptcha($secret, trim($hash), trim($number))) {
			if (isset($params['assign_error'])) {
				$tpl->assign($params['assign_error'], $error);
			}
			else {
				throw new UserException($error);
			}
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

		if (!empty($params['block_urls']) && preg_match('!https?://!', $params['subject'] . $params['body'])) {
			throw new UserException('Merci de ne pas inclure d\'adresse web (http:…) dans le message');
		}

		static $external = 0;
		static $internal = 0;

		if (is_string($params['to'])) {
			$params['to'] = [$params['to']];
		}

		if (!count($params['to'])) {
			throw new Brindille_Exception(sprintf('Ligne %d: aucune adresse destinataire n\'a été précisée pour la fonction "mail"', $line));
		}

		foreach ($params['to'] as &$to) {
			$to = trim($to);
			Email::validateAddress($to);
		}

		unset($to);

		$db = DB::getInstance();
		$internal_count = $db->count('users', $db->where($email_field, 'IN', $params['to']));
		$external_count = count($params['to']) - $internal_count;

		if (($external_count + $external) > 1) {
			throw new Brindille_Exception(sprintf('Ligne %d: l\'envoi d\'email à une adresse externe est limité à un envoi par page', $line));
		}

		if (($internal_count + $internal) > 10) {
			throw new Brindille_Exception(sprintf('Ligne %d: l\'envoi d\'email à une adresse interne est limité à un envoi par page', $line));
		}

		if ($external_count && preg_match_all('!(https?://.*?)(?=\s|$)!', $params['subject'] . ' ' . $params['body'], $match, PREG_PATTERN_ORDER)) {
			foreach ($match[1] as $m) {
				if (0 !== strpos($m, WWW_URL) && 0 !== strpos($m, ADMIN_URL)) {
					throw new Brindille_Exception(sprintf('Ligne %d: l\'envoi d\'email à une adresse externe interdit l\'utilisation d\'une adresse web autre que le site de l\'association : %s', $line, $m));
				}
			}
		}

		$context = count($params['to']) == 1 ? Emails::CONTEXT_PRIVATE : Emails::CONTEXT_BULK;
		Emails::queue($context, $params['to'], null, $params['subject'], $params['body']);

		$internal += $internal_count;
		$external_count += $external_count;
	}

	static public function debug(array $params, Brindille $tpl)
	{
		if (!count($params)) {
			$params = $tpl->getAllVariables();
		}

		$dump = htmlspecialchars(ErrorManager::dump($params));

		// FIXME: only send back HTML when content-type is text/html, or send raw text
		$out = sprintf('<pre style="background: yellow; color: black; padding: 5px; overflow: auto">%s</pre>', $dump);

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

		if (!empty($params['capture']) && preg_match('/^[a-z0-9_]+$/', $params['capture'])) {
			$ut::__assign([$params['capture'] => $include->fetch()], $ut, $line);
		}
		else {
			$include->display();
		}

		if (isset($params['keep'])) {
			$keep = explode(',', $params['keep']);
			$keep = array_map('trim', $keep);

			foreach ($keep as $name) {
				// Transmit variables
				$ut::__assign(['var' => $name, 'value' => $include->get($name)], $ut, $line);
			}
		}

		// Transmit nocache to parent template
		if ($include->get('nocache')) {
			$ut::__assign(['nocache' => true], $ut, $line);
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
