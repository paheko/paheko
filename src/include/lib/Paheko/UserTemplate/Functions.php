<?php

namespace Paheko\UserTemplate;

use KD2\Brindille_Exception;
use KD2\DB\DB_Exception;
use KD2\ErrorManager;
use KD2\HTTP;
use KD2\JSONSchema;
use KD2\Security;

use Paheko\API;
use Paheko\APIException;
use Paheko\Config;
use Paheko\CSV_Custom;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Extensions;
use Paheko\Template;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\UserTemplate\UserTemplate;
use Paheko\Email\Emails;
use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Entities\Module;
use Paheko\Entities\Email\Email;
use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

use const Paheko\{ROOT, WWW_URL, BASE_URL, LOCAL_SECRET_KEY};

class Functions
{
	const FUNCTIONS_LIST = [
		'include',
		'http',
		'debug',
		'error',
		'read',
		'save',
		'delete',
		'admin_header',
		'admin_footer',
		'password_input',
		'signature',
		'captcha',
		'mail',
		'button',
		'delete_form',
		'form_errors',
		'admin_files',
		'delete_file',
		'api',
		'csv',
		'call',
	];

	const COMPILE_FUNCTIONS_LIST = [
		':break'    => [self::class, 'compile_break'],
		':continue' => [self::class, 'compile_continue'],
		':return'   => [self::class, 'compile_return'],
		':exit'     => [self::class, 'compile_exit'],
		':yield'    => [self::class, 'compile_yield'],
		':redirect' => [self::class, 'compile_redirect'],
	];

	/**
	 * Compile function to break inside a loop
	 */
	static public function compile_break(string $name, string $params, UserTemplate $tpl, int $line)
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

	/**
	 * Compile function to continue inside a loop
	 */
	static public function compile_continue(string $name, string $params, UserTemplate $tpl, int $line): string
	{
		$in_loop = 0;
		foreach ($tpl->_stack as $element) {
			if ($element[0] == $tpl::SECTION) {
				$in_loop++;
			}
		}

		$i = ctype_digit(trim($params)) ? (int)$params : 1;

		if ($in_loop < $i) {
			throw new Brindille_Exception('"continue" function can only be used inside a section');
		}

		return sprintf('<?php continue(%d); ?>', $i);
	}

	static public function compile_return(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$parent = $tpl->_getStack($tpl::SECTION, 'define');

		// Allow {{:return value="test"}} inside a user-defined modifier only
		if ($parent && ($parent[2]['context'] ?? null) === 'modifier') {
			$params = $tpl->_parseArguments($params_str, $line);

			return sprintf('<?php return %s; ?>', $tpl->_exportArgument($params['value'] ?? 'null'));
		}
		// But not outside
		elseif (!empty($params_str)) {
			throw new Brindille_Exception('"return" function cannot have parameters in this context');
		}

		return '<?php return; ?>';
	}

	static public function compile_exit(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$parent = $tpl->_getStack($tpl::SECTION, 'define');

		if (!$parent) {
			throw new Brindille_Exception('"exit" function cannot be called in this context');
		}

		return '<?php return; ?>';
	}

	static public function compile_yield(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$parent = $tpl->_getStack($tpl::SECTION, 'define');

		// Only allow {{:yield}} inside a user-defined function
		if (!$parent || ($parent[2]['context'] ?? null) !== 'section') {
			throw new Brindille_Exception('"yield" can only be used inside a "define" section');
		}

		$params = $tpl->_parseArguments($params_str, $line);

		return sprintf('<?php yield %s; ?>', $tpl->_exportArguments($params));
	}

	static public function compile_redirect(string $name, string $params, UserTemplate $tpl, int $line): string
	{
		$params = $tpl->_parseArguments($params, $line);
		$params = $tpl->_exportArguments($params);

		return sprintf('<?php return %s::redirect(%s); ?>', self::class, $params);
	}

	static public function redirect(array $params): string
	{
		if (!empty($params['permanent']) && !isset($_GET['_dialog'])) {
			@http_response_code(301);
		}

		$self = $params['url'] ?? ($params['self'] ?? null);
		// Legacy force/to parameters TODO remove
		$parent = $params['parent'] ?? ($params['force'] ?? null);
		$reload = $params['reload'] ?? ($params['to'] ?? null);

		// Redirect inside dialog
		if ($self) {
			Utils::redirect($self, false);
		}
		elseif ($parent) {
			Utils::redirectDialog($parent, false);
		}
		elseif (isset($_GET['_dialog'])) {
			Utils::reloadParentFrame(null, false);
		}
		else {
			if ($reload === true) {
				$reload = null;
			}

			Utils::redirectDialog($reload, false);
		}

		return 'STOP';
	}

	static public function admin_header(array $params): string
	{
		$tpl = Template::getInstance();
		$tpl->assign($params);

		if (Session::getInstance()->isLogged()) {
			$tpl->assign('plugins_menu', Extensions::listMenu(Session::getInstance()));
		}

		return $tpl->fetch('_head.tpl');
	}

	static public function admin_footer(array $params): string
	{
		$tpl = Template::getInstance();
		$tpl->assign($params);
		return $tpl->fetch('_foot.tpl');
	}

	static public function password_input(): string
	{
		$tpl = Template::getInstance();
		return $tpl->fetch('users/_password_form.tpl');
	}

	static public function save(array $params, UserTemplate $tpl, int $line): void
	{
		if (!$tpl->module) {
			throw new Brindille_Exception('Module name could not be found');
		}

		$db = DB::getInstance();

		if (isset($params['from'])) {
			if (!is_array($params['from'])) {
				throw new Brindille_Exception('"from" parameter is not an array');
			}

			$from = $params['from'];
			unset($params['from']);
			$db->begin();

			try {
				foreach ($from as $key => $row) {
					if (!is_array($row) && !is_object($row)) {
						throw new Brindille_Exception('"from" parameter item is not an array on index: ' . $key);
					}

					self::save(array_merge($params, (array)$row), $tpl, $line);
				}
			}
			catch (Brindille_Exception|DB_Exception $e) {
				$db->rollback();
				throw $e;
			}

			$db->commit();
			return;
		}

		$table = 'module_data_' . $tpl->module->name;

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
			$where_value = null;
			$field = null;
		}

		$key = $params['key'] ?? null;
		$id = $params['id'] ?? null;
		$assign_new_id = $params['assign_new_id'] ?? null;
		$validate = $params['validate_schema'] ?? null;
		$validate_only = $params['validate_only'] ?? null;

		unset($params['key'], $params['id'], $params['assign_new_id'], $params['validate_schema'], $params['validate_only']);

		if ($key == 'config') {
			$result = $db->firstColumn(sprintf('SELECT config FROM %s WHERE name = ?;', Module::TABLE), $tpl->module->name);
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

		if (!empty($validate)) {
			$schema = self::_readFile($validate, 'validate_schema', $tpl, $line);

			if ($validate_only && is_string($validate_only)) {
				$validate_only = explode(',', $validate_only);
				$validate_only = array_map('trim', $validate_only);
			}
			else {
				$validate_only = null;
			}

			try {
				$s = JSONSchema::fromString($schema);

				if ($validate_only) {
					$s->validateOnly($params, $validate_only);
				}
				else {
					$s->validate($params);
				}
			}
			catch (\RuntimeException $e) {
				throw new Brindille_Exception(sprintf("ligne %d: impossible de valider le schéma:\n%s\n\n%s",
					$line, $e->getMessage(), json_encode($params, JSON_PRETTY_PRINT)));
			}
		}

		$value = json_encode($params);

		if ($key == 'config') {
			$db->update(Module::TABLE, ['config' => $value], 'name = :name', ['name' => $tpl->module->name]);
			return;
		}

		$document = $value;
		if (!$result) {
			$db->insert($table, compact('id', 'document', 'key'));

			if ($assign_new_id) {
				$tpl->assign($assign_new_id, $db->lastInsertId());
			}
		}
		else {
			$db->update($table, compact('document'), sprintf('%s = :match', $field), ['match' => $where_value]);
		}
	}

	static public function delete(array $params, UserTemplate $tpl, int $line): void
	{
		if (!$tpl->module) {
			throw new Brindille_Exception('Module name could not be found');
		}

		$db = DB::getInstance();
		$table = 'module_data_' . $tpl->module->name;

		// No table? No problem!
		if (!$tpl->module->hasTable()) {
			return;
		}

		$where = [];
		$args = [];
		$i = 0;

		foreach ($params as $key => $value) {
			if ($key[0] == ':') {
				$args[substr($key, 1)] = $value;
			}
			elseif ($key == 'where') {
				$where[] = Sections::_moduleReplaceJSONExtract($value, $table);
			}
			else {
				if ($key == 'id') {
					$value = (int) $value;
				}

				if ($key !== 'id' && $key !== 'key') {
					$args['key_' . $i] = '$.' . $key;
					$key = sprintf('json_extract(document, :key_%d)', $i);
				}

				$where[] = $key . ' = :value_' . $i;
				$args['value_' . $i] = $value;
				$i++;
			}
		}

		$where = implode(' AND ', $where);
		$db->delete($table, $where, $args);
	}

	static public function captcha(array $params, UserTemplate $tpl, int $line): string
	{
		$secret = md5(LOCAL_SECRET_KEY . Utils::getSelfURL(false));
		$hash = null;
		$number = null;

		if (isset($params['html'])) {
			$c = Security::createCaptcha($secret, $params['lang'] ?? 'fr');
			return sprintf('<label for="f_c_42">Merci d\'écrire <strong><q>&nbsp;%s&nbsp;</q></strong> en chiffres pour montrer que vous êtes humain&nbsp;:</label>
				<input name="f_c_42" id="f_c_42" placeholder="Ex : 1234" size="8" />
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

		return '';
	}

	static public function mail(array $params, UserTemplate $ut, int $line)
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

		$attachments = [];

		if (!empty($params['attach_file'])) {
			$attachments = (array) $params['attach_file'];

			foreach ($attachments as &$file) {
				$f = Files::get($file);

				if (!$f) {
					throw new UserException(sprintf('Le fichier à joindre "%s" n\'existe pas', $file));
				}

				if (!$f->canRead()) {
					throw new UserException(sprintf('Vous n\'avez pas le droit d\'accéder au fichier à joindre "%s"', $file));
				}

				$file = $f;
			}

			unset($file);
		}
		elseif (!empty($params['attach_from'])) {
			if (empty($ut->module)) {
				throw new UserException('"attach_from" can only be called from within a module');
			}

			$attachments = (array) $params['attach_from'];

			foreach ($attachments as &$file) {
				$file = self::getFilePath($file, 'attach_from', $ut, $line);
				$file = $ut->fetchToAttachment($file);
			}

			unset($file);
		}

		static $external = 0;
		static $internal = 0;

		if (is_string($params['to'])) {
			$params['to'] = (array) $params['to'];
		}

		$params['to'] = array_filter($params['to']);

		if (!count($params['to'])) {
			throw new Brindille_Exception(sprintf('Ligne %d: aucune adresse destinataire n\'a été précisée pour la fonction "mail"', $line));
		}

		foreach ($params['to'] as &$to) {
			$to = trim($to);
			Email::validateAddress($to);
		}

		unset($to);

		// Restrict sending recipients
		if (!$ut->isTrusted()) {
			$db = DB::getInstance();
			$email_field = DynamicFields::getFirstEmailField();
			$internal_count = (int) $db->count('users', $db->where($email_field, 'IN', $params['to']));
			$external_count = intval(count($params['to']) - $internal_count);

			if (($external_count + $external) > 1) {
				throw new Brindille_Exception(sprintf('Ligne %d: l\'envoi d\'email à une adresse externe est limité à un envoi par page', $line));
			}

			if (($internal_count + $internal) > 10) {
				throw new Brindille_Exception(sprintf('Ligne %d: l\'envoi d\'email à une adresse interne est limité à 10 envois par page', $line));
			}

			if ($external_count
				&& preg_match_all('!(https?://.*?)(?=\s|$)!', $params['subject'] . ' ' . $params['body'], $match, PREG_PATTERN_ORDER)) {
				$config = Config::getInstance();

				foreach ($match[1] as $m) {
					$allowed = false;

					if (0 === strpos($m, WWW_URL)) {
						$allowed = true;
					}
					elseif (0 === strpos($m, BASE_URL)) {
						$allowed = true;
					}
					elseif ($config->org_web && 0 === strpos($m, $config->org_web)) {
						$allowed = true;
					}

					if (!$allowed) {
						throw new Brindille_Exception(sprintf('Ligne %d: l\'envoi d\'email à une adresse externe interdit l\'utilisation d\'une adresse web autre que le site de l\'association : %s', $line, $m));
					}
				}
			}
		}

		if (!empty($params['notification'])) {
			$context = Emails::CONTEXT_NOTIFICATION;
		}
		elseif (count($params['to']) == 1) {
			$context = Emails::CONTEXT_PRIVATE;
		}
		else {
			$context = Emails::CONTEXT_BULK;
		}

		Emails::queue($context, $params['to'], null, $params['subject'], $params['body'], $attachments);

		if (!$ut->isTrusted()) {
			$internal += $internal_count;
			$external_count += $external_count;
		}
	}

	static public function debug(array $params, UserTemplate $tpl)
	{
		if (!count($params)) {
			$params = $tpl->getAllVariables();
		}

		$dump = htmlspecialchars(ErrorManager::dump($params));

		// FIXME: only send back HTML when content-type is text/html, or send raw text
		$out = sprintf('<pre style="background: yellow; color: black; padding: 5px; overflow: auto">%s</pre>', $dump);

		if (!empty($params['stop'])) {
			echo $out;
			exit;
		}

		return $out;
	}

	static public function error(array $params, UserTemplate $tpl, int $line)
	{
		if (isset($params['admin'])) {
			throw new Brindille_Exception($params['admin']);
		}

		throw new UserException($params['message'] ?? 'Erreur du module');
	}

	static protected function getFilePath(?string $path, string $arg_name, UserTemplate $ut, int $line)
	{
		if (empty($path)) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "%s" manquant', $line, $arg_name));
		}

		if (substr($path, 0, 2) == './') {
			$path = Utils::dirname($ut->_tpl_path) . substr($path, 1);
		}
		elseif (substr($path, 0, 1) != '/') {
			$path = Utils::dirname($ut->_tpl_path) . '/' . $path;
		}

		$parts = explode('/', $path);
		$out = [];

		foreach ($parts as $part) {
			if ($part == '..') {
				array_pop($out);
			}
			else {
				$out[] = $part;
			}
		}

		$out = implode('/', $out);

		if (preg_match('!\.\.|://|/\.|^\.!', $out)) {
			throw new Brindille_Exception(sprintf('Ligne %d: argument "%s" invalide', $line, $arg_name));
		}

		return $out;
	}

	static public function _readFile(string $file, string $arg_name, UserTemplate $ut, int $line): string
	{
		$path = self::getFilePath($file, $arg_name, $ut, $line);

		$file = Files::get(File::CONTEXT_MODULES . '/' . $path);

		if ($file) {
			$content = $file->fetch();
		}
		elseif (file_exists(ROOT . '/modules/' . $path)) {
			$content = file_get_contents(ROOT . '/modules/' . $path);
		}
		else {
			throw new Brindille_Exception(sprintf('Ligne %d : le fichier appelé "%s" n\'existe pas', $line, $path));
		}

		return $content;
	}

	static public function read(array $params, UserTemplate $ut, int $line): string
	{
		$content = self::_readFile($params['file'] ?? '', 'file', $ut, $line);

		if (!empty($params['assign'])) {
			$ut::__assign(['var' => $params['assign'], 'value' => $content], $ut, $line);
			return '';
		}

		return $content;
	}

	static public function signature(): string
	{
		$config = Config::getInstance();
		$url = $config->fileURL('signature') ?? $config->fileURL('logo');

		if (!$url) {
			return '';
		}

		return sprintf('<figure class="signature"><img src="%s" alt="Signature" /></figure>', $url);
	}

	static public function include(array $params, UserTemplate $ut, int $line): void
	{
		$path = self::getFilePath($params['file'] ?? null, 'file', $ut, $line);

		// Avoid recursive loops
		$from = $ut->get('included_from') ?? [];

		if (in_array($path, $from)) {
			throw new Brindille_Exception(sprintf('Ligne %d : boucle infinie d\'inclusion détectée : %s', $line, $path));
		}

		try {
			$include = new UserTemplate($path);
			$include->setParent($ut);
		}
		catch (\InvalidArgumentException $e) {
			throw new Brindille_Exception(sprintf('Ligne %d : fonction "include" : le fichier à inclure "%s" n\'existe pas', $line, $path));
		}

		$params['included_from'] = array_merge($from, [$path]);

		$include->assignArray(array_merge($ut->getAllVariables(), $params));

		if (!empty($params['capture'])) {
			if (!preg_match($ut::RE_VALID_VARIABLE_NAME, $params['capture'])) {
				throw new Brindille_Exception('Nom de variable invalide : ' . $params['capture']);
			}

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

		// Copy/overwrite user-defined functions to parent template
		$include->copyUserFunctionsTo($ut);

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
			throw new Brindille_Exception('Le paramètre "redirect" a été supprimé');
		}

		if (isset($params['code'])) {
			$tpl->setHeader('code', $params['code']);
		}

		if (!empty($params['type'])) {
			if ($params['type'] == 'pdf') {
				$params['type'] = 'application/pdf';
			}

			$tpl->setHeader('type', $params['type']);
		}

		if (isset($params['download'])) {
			$tpl->setHeader('disposition', 'attachment');
			$tpl->setHeader('filename', $params['download']);
		}
		elseif (isset($params['inline'])) {
			$tpl->setHeader('disposition', 'inline');
			$tpl->setHeader('filename', $params['inline']);
		}
	}

	static public function _getFormKey(): string
	{
		$uri = preg_replace('![^/]*$!', '', Utils::getSelfURI(false));
		return 'form_' . md5($uri);
	}

	/**
	 * @override
	 */
	static public function button(array $params): string
	{
		// Always add CSRF protection when a submit button is present in the form
		if (isset($params['type']) && $params['type'] == 'submit') {
			$key = self::_getFormKey();
			$params['csrf_key'] = $key;
		}

		return CommonFunctions::button($params);
	}

	/**
	 * @override
	 */
	static public function delete_form(array $params, UserTemplate $tpl): string
	{
		$params['csrf_key'] = self::_getFormKey();
		return self::form_errors([], $tpl) . CommonFunctions::delete_form($params);
	}

	static public function form_errors(array $params, UserTemplate $tpl): string
	{
		$errors = $tpl->get('form_errors');

		if (empty($errors)) {
			return '';
		}

		foreach ($errors as &$error) {
			if ($error instanceof UserException) {
				if ($html = $error->getHTMLMessage()) {
					$message = $html;
				}
				else {
					$message = nl2br(htmlspecialchars($error->getMessage()));
				}

				if ($error->hasDetails()) {
					$message = '<h3>' . $message . '</h3>' . $error->getDetailsHTML();
				}

				$error = $message;
			}
			else {
				$error = nl2br(htmlspecialchars($error));
			}
		}

		return '<div class="block error"><ul><li>' . implode('</li><li>', $errors) . '</li></ul></div>';
	}

	static public function admin_files(array $params, UserTemplate $ut, int $line): string
	{
		if (empty($ut->module)) {
			throw new Brindille_Exception('Module could not be found');
		}

		$tpl = Template::getInstance();

		if (!isset($params['edit'])) {
			$params['edit'] = false;
		}

		if (!isset($params['upload'])) {
			$params['upload'] = $params['edit'];
		}

		if (isset($params['path']) && preg_match('!/\.|\.\.!', $params['path'])) {
			throw new Brindille_Exception(sprintf('Line %d: "path" parameter is invalid: "%s"', $line, $params['path']));
		}

		$path = isset($params['path']) && preg_match('/^[a-z0-9_-]+$/i', $params['path']) ? '/' . $params['path'] : '';

		$tpl->assign($params);
		$tpl->assign('path', $ut->module->storage_root() . $path);
		return '<div class="attachments noprint"><h3 class="ruler">Fichiers joints</h3>' . $tpl->fetch('common/files/_context_list.tpl') . '</div>';
	}

	static public function delete_file(array $params, UserTemplate $ut, int $line): void
	{
		if (empty($ut->module)) {
			throw new Brindille_Exception('Module could not be found');
		}

		if (empty($params['path'])) {
			throw new Brindille_Exception(sprintf('Line %d: "path" parameter is missing or empty', $line));
		}

		if (preg_match('!/\.|\.\.!', $params['path'])) {
			throw new Brindille_Exception(sprintf('Line %d: "path" parameter is invalid: "%s"', $line, $params['path']));
		}

		Files::delete($ut->module->storage_root() . '/' . $params['path']);
	}

	static public function api(array $params, UserTemplate $ut, int $line): void
	{
		if (empty($params['path'])) {
			throw new Brindille_Exception('"path" parameter is missing');
		}

		if (empty($params['method'])) {
			throw new Brindille_Exception('"method" parameter is missing');
		}

		$path = trim($params['path'], '/');
		$method = strtoupper($params['method']);
		$assign = $params['assign'] ?? null;
		$assign_code = $params['assign_code'] ?? null;
		$fail = $params['fail'] ?? true;
		$access_level = null;
		$url = null;

		unset($params['method'], $params['path'], $params['assign'], $params['assign_code'], $params['fail']);

		if (isset($params['url'])) {
			if (empty($params['user'])) {
				throw new Brindille_Exception('"user" parameter is missing');
			}

			if (empty($params['password'])) {
				throw new Brindille_Exception('"password" parameter is missing');
			}

			$url = str_replace('://', '://' . $params['user'] . '@' . $params['password'], $params['url']);
			unset($params['user'], $params['password'], $params['url']);
		}
		else {
			$access_level = $params['access'] ?? 'admin';
			unset($params['access_level']);
		}

		$body = null;

		if ($method !== 'GET' && array_key_exists('body', $params)) {
			$body = $params['body'];
			unset($params['body']);
		}

		$code = null;
		$return = null;

		// External HTTP request
		if ($url) {
			$http = new HTTP;
			$url = rtrim($url, '/') . '/' . $path;
			$body ??= json_encode($params);

			if ($method === 'POST') {
				$r = $http->POST($url, $body, $http::JSON);
			}
			else {
				$r = $http->GET($url);
			}

			if ($fail && $r->fail) {
				$body = json_decode($r->body);
				throw new Brindille_Exception(sprintf('External API request failed: %d - %s', $r->status, $body->error ?? $r->error));
			}

			$code = $r->status;
			$return = $r->body;

			if ($assign && isset($r->headers['Content-Type']) && false !== strpos($r->headers['Content-Type'], '/json')) {
				$return = @json_decode($return);
			}
		}
		// Internal request
		else {
			$api = new API($method, $path, $params, false);

			if ($access_level) {
				$api->setAccessLevelByName($access_level);
			}

			$api->setAllowedFilesRoot($ut->module->storage_root());

			try {
				$return = $api->route();
			}
			catch (APIException $e) {
				if ($fail) {
					throw new Brindille_Exception(sprintf('Internal API request failed: %d - %s', $e->getCode(), $e->getMessage()));
				}
			}
		}

		if ($assign_code) {
			$ut->assign($assign_code, $code);
		}

		if ($assign) {
			$ut->assign($assign, $return);
		}
	}

	static public function csv(array $params, UserTemplate $ut, int $line): string
	{
		if (!$ut->module) {
			throw new Brindille_Exception('Module name could not be found');
		}

		static $sheets = [];

		$action = $params['action'] ?? null;

		if (empty($sheets) && $action !== 'initialize') {
			throw new Brindille_Exception('"action" parameter is missing or is not "initialize"');
		}

		if (empty($params['name'])) {
			if (count($sheets)) {
				$name = key($sheets);
			}
			else {
				$name = 'default';
				$name = $ut->module->name . '_' . $name;
			}
		}
		else {
			$name = $params['name'];
			$name = $ut->module->name . '_' . $name;
		}

		if ($action === 'initialize') {
			$session = Session::getInstance();

			if (!$session->isLogged()) {
				throw new Brindille_Exception('This function may only be called by a logged-in user');
			}

			if (empty($params['columns']) || !is_array($params['columns'])) {
				throw new Brindille_Exception('"columns" parameter is missing or is empty');
			}

			$csv = $sheets[$name] = new CSV_Custom($session, $name);

			$csv->setColumns($params['columns']);

			if (!empty($params['mandatory_columns']) && is_array($params['mandatory_columns'])) {
				$csv->setMandatoryColumns($params['mandatory_columns']);
			}

			if (!empty($_POST['csv_cancel'])) {
				$csv->clear();
				Utils::redirect(Utils::getSelfURI());
			}
			elseif (($key = self::_getFormKey()) && \KD2\Form::tokenCheck($key)) {
				if (!empty($_POST['csv_upload'])) {
					$csv->load($_FILES['csv'] ?? []);
					Utils::redirect(Utils::getSelfURI());
				}
				elseif (!empty($_POST['translation_table'])) {
					$csv->setTranslationTable($_POST['translation_table']);
					Utils::redirect(Utils::getSelfURI());
				}
			}
		}

		if (empty($sheets[$name])) {
			throw new Brindille_Exception(sprintf('"name" parameter is referencing an unknown instance: "%s"', $name));
		}

		$csv =& $sheets[$name];

		if ($action === 'clear') {
			$csv->clear();
		}
		elseif ($action === 'form') {
			if ($csv->ready()) {
				return '';
			}

			if (!$csv->loaded()) {
				$tpl = Template::getInstance();
				$tpl->assign('csv', $csv);
				$help = $tpl->fetch('common/_csv_help.tpl');
				$input = CommonFunctions::input([
					'required' => true,
					'label'    => $params['label'] ?? 'Fichier',
					'name'     => 'csv',
					'type'     => 'file',
				]);
				$button = self::button([
					'type'  => 'submit',
					'name'  => 'csv_upload',
					'label' => 'Configurer',
					'shape' => 'right',
					'class' => 'main',
				]);

				return sprintf('<form method="post" action="" enctype="multipart/form-data"><fieldset><legend>%s</legend><dl>%s%s</dl></fieldset><p class="submit">%s</p></form>',
					htmlspecialchars($params['legend'] ?? 'Charger un fichier'),
					$input,
					$help,
					$button
				);
			}
			else {
				$tpl = Template::getInstance();
				$tpl->assign('csv', $csv);
				$form = $tpl->fetch('common/_csv_match_columns.tpl');

				$button = self::button([
					'type'  => 'submit',
					'name'  => 'csv_set_translation_table',
					'label' => 'Continuer',
					'shape' => 'right',
					'class' => 'main',
				]);

				return sprintf('<form method="post" action="">%s<p class="submit">%s%s</p></form>',
					$form,
					self::csv(['action' => 'cancel_button'], $ut, $line),
					$button
				);
			}
		}
		elseif ($action === 'cancel_button') {
			return self::button([
				'type'  => 'submit',
				'name'  => 'csv_cancel',
				'label' => 'Annuler',
				'shape' => 'left',
			]);
		}
		elseif ($action && $action !== 'initialize') {
			throw new Brindille_Exception('Unknown action: ' . $action);
		}

		$assign = $params['assign'] ?? null;

		if ($assign) {
			$ut->assign($assign, $csv->export());
		}

		return '';
	}

	static public function call(array $params, UserTemplate $tpl, int $line): void
	{
		if (empty($params['function'])) {
			throw new Brindille_Exception('Missing "function" parameter for "call" function');
		}

		$name = $params['function'];
		unset($params['function']);
		$tpl->callUserFunction('function', $name, $params, $line);
	}
}
