<?php

namespace Paheko\UserTemplate;

use KD2\Brindille_Exception;
use KD2\DB\DB_Exception;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Extensions;
use Paheko\Template;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\Users\Session;
use Paheko\Entities\Web\Page;
use Paheko\Web\Web;
use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Entities\Accounting\Year;
use Paheko\Users\DynamicFields;
use Paheko\UserTemplate\Modules\LegacySections;

class Sections
{
	const SECTIONS_LIST = [
		'call',
		'load',
		'list',
		'categories',
		'articles',
		'pages',
		'breadcrumbs',
		'images',
		'documents',
		'attachments',
		'users',
		'subscriptions',
		'transactions',
		'transaction_lines',
		'transaction_users',
		'accounts',
		'balances',
		'years',
		'projects',
		'sql',
		'restrict',
		'module',
		'extension',
		'files',
	];

	const COMPILE_SECTIONS_LIST = [
		'#select'     => [self::class, 'selectStart'],
		'/select'     => [self::class, 'selectEnd'],
		'#form'       => [self::class, 'formStart'],
		'/form'       => [self::class, 'formEnd'],
		'else:form'   => [self::class, 'formElse'],
		'#capture'    => [self::class, 'captureStart'],
		'/capture'    => [self::class, 'captureEnd'],
		'#define'     => [self::class, 'defineStart'],
		'else:define' => [self::class, 'defineElse'],
		'/define'     => [self::class, 'defineEnd'],
	];

	const SQL_RESERVED_PARAMS = [
		'select',
		'tables',
		'where',
		'group',
		'having',
		'order',
		'begin',
		'limit',
		'assign',
		'debug',
		'count',
	];

	/**
	 * List of tables and columns that are restricted in SQL queries
	 *
	 * ~column means the column will always be returned as NULL
	 * -column or !table means trying to access this column or table will return an error
	 * see KD2/DB/SQLite3 code for details
	 *
	 * Note: column restrictions are only possible with PHP >= 8.0
	 */
	const SQL_TABLES = [
		// Allow access to all tables
		'*' => null,
		// Restrict access to private fields in users
		'users' => ['~password', '~pgp_key', '~otp_secret', '~otp_recovery_codes'],
		// Restrict access to some private tables
		'!emails' => null,
		'!emails_queue' => null,
		'!compromised_passwords_cache' => null,
		'!compromised_passwords_cache_ranges' => null,
		'!api_credentials' => null,
		'!plugins_signals' => null,
		'!config' => null,
		'!users_sessions' => null,
		'!logs' => null,
	];

	static protected $_cache = [];

	static public function selectStart(string $name, string $sql, UserTemplate $tpl, int $line): string
	{
		$sql = strtok($sql, ';');
		$extra_params = strtok('');

		$i = 0;
		$params = '';

		$sql = preg_replace_callback('/\{(.*?)\}/', function ($match) use (&$params, &$i) {
			// Raw SQL
			if ('!' === substr($match[1], 0, 1)) {
				$params .= ' !' . $i . '=' . substr($match[1], 1);
				return '!' . $i++;
			}
			else {
				$params .= ' :p' . $i . '=' . $match[1];
				return ':p' . $i++;
			}
		}, $sql);

		$sql = 'SELECT ' . $sql;
		$sql = var_export($sql, true);

		$params .= ' sql=' . $sql . ' ' . $extra_params;

		return $tpl->_section('sql', $params, $line);
	}

	static public function selectEnd(string $name, string $params, UserTemplate $tpl, int $line): string
	{
		return $tpl->_close('sql', '{{/select}}');
	}

	static public function formStart(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		// Nested #form sections are not allowed
		foreach ($tpl->_stack as $section) {
			if ($section[0] === $tpl::SECTION && $section[1] === 'form') {
				throw new Brindille_Exception('Cannot use a #form section inside a #form section');
			}
		}

		$tpl->_push($tpl::SECTION, 'form');

		$params = $tpl->_parseArguments($params_str, $line);

		if (isset($params['on'])
			&& ($on = $tpl->getValueFromArgument($params['on']))) {

			if (!preg_match($tpl::RE_VALID_VARIABLE_NAME, $on)) {
				throw new Brindille_Exception('Nom de variable invalide : ' . $on);
			}

			$if = sprintf('$_POST[%s]', var_export($on, true));
		}
		else {
			$if = '$_POST';
		}

		unset($params['on']);
		//$params = $tpl->_exportArguments($params);

		return sprintf('<?php if (!empty(%s)): ', $if)
			. 'try { '
			. '$fail = false; ' // We define this for committing changes later if everything is fine
			. '\Paheko\DB::getInstance()->begin(); '
			. '$hash = \Paheko\UserTemplate\Functions::_getFormKey(); '
			. 'if (!\KD2\Form::tokenCheck($hash)) { '
			. 'throw new \Paheko\ValidationException(\'Une erreur est survenue, merci de bien vouloir renvoyer le formulaire.\'); '
			. '} foreach ([null] as $_): ?>';
		/*
			. sprintf('$params = %s; ', $params)
			. '$form_errors = []; '
			. 'if (!\KD2\Form::check(\'form_\' . $hash, $rules, $form_errors)) { '
			. '$this->assign(\'form_errors\', \KD2\Form::getErrorMessages($form_errors, \'fr\')); '
			. '} ?>';
		*/
	}

	static public function formElse(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		return '<?php '
			. 'endforeach; '
			. '} catch (\Paheko\UserException $e) { '
			. '$this->assign(\'form_errors\', [$e]); '
			. '$fail = true; '
			. '?>';
	}

	static public function formEnd(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		if ($tpl->_lastName() !== 'form') {
			throw new Brindille_Exception(sprintf('"%s": block closing does not match last block "%s" opened', $name . $params_str, $tpl->_lastName()));
		}

		$type = $tpl->_lastType();
		$tpl->_pop();

		$out = '';

		if ($type === $tpl::SECTION) {
			$out .= self::formElse($name, $params_str, $tpl, $line);
		}

		$out .= '<?php '
			. '} catch (\Throwable $e) { '
			. '$fail = true; '
			. 'throw $e; '
			. '} finally { '
			. '$db = \Paheko\DB::getInstance(); '
			. 'if ($fail) { $db->rollback(); } ' // Rollback DB if something failed
			. 'else { $db->commit(); } ' // Commit changes if no exception was raised
			. '} unset($db, $fail); ' // Close finally block
			. 'endif; ?>';

		$out = str_replace(' ?><?php ', ' ', $out);

		return $out;
	}

	static public function captureStart(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$params = $tpl->_parseArguments($params_str, $line);

		if (!isset($params['assign']) || !is_string($params['assign'])) {
			throw new Brindille_Exception(sprintf('"%s": missing "assign" parameter', $name));
		}

		$assign = $tpl->getValueFromArgument($params['assign']);

		$tpl->_push($tpl::SECTION, 'capture');

		return sprintf('<?php $capture_assign ??= []; $capture_assign[] = %s; @ob_start(); ?>',
			var_export($assign, true));
	}

	static public function captureEnd(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$last = $tpl->_lastName();

		if ($last !== 'capture') {
			throw new Brindille_Exception(sprintf('"%s": block closing does not match last block "%s" opened', $name . $params_str, $last));
		}

		$tpl->_pop();

		return '<?php $this->assign(array_pop($capture_assign), ob_get_clean()); ?>';
	}

	/**
	 * Start of user-defined function block
	 */
	static public function defineStart(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$params = $tpl->_parseArguments($params_str, $line);
		$context = array_intersect_key(['modifier' => null, 'function' => null, 'section' => null], $params);

		if (count($context) > 1) {
			throw new Brindille_Exception('"define" only allows one of "modifier", "function" or "section" parameters');
		}
		elseif (!count($context)) {
			throw new Brindille_Exception('"define": missing "modifier", "function" or "section" parameter');
		}

		$context = key($context);
		$name = $tpl->getValueFromArgument($params[$context]);

		if (!preg_match($tpl::RE_VALID_VARIABLE_NAME, $name)) {
			throw new Brindille_Exception(sprintf('Invalid syntax for %s name \'%s\'', $context, $name));
		}

		// Avoid weird stuff (like defining a function inside a function):
		// only allow functions to be defined at the root level
		if (count($tpl->_stack)) {
			throw new Brindille_Exception(sprintf('%s cannot be defined inside a condition or section', $context));
		}

		$tpl->_push($tpl::SECTION, 'define', compact('context', 'name'));

		return sprintf('<?php '
			. '$this->registerUserFunction(%s, %s, function (array $params, int $line) { '
			// Store function name here, might be useful for handling errors
			. '$context = %1$s; $name = %2$s; '
			// Pass variables to template, either as '$params' variable for modifiers,
			// or extract all parameters as variables for functions/sections
			. '$this->_variables[] = %s; '
			// Put all function body in a try
			. 'try { ?>',
			var_export($context, true),
			var_export($name, true),
			$context === 'modifier' ? 'compact(\'params\')' : '$params'
		);
	}

	static public function defineElse(string $name, string $params_str, UserTemplate $tpl, int $line): void
	{
		throw new Brindille_Exception('\'else\' cannot be used with #define sections');
	}

	static public function defineEnd(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$last = $tpl->_lastName();

		if ($last !== 'define') {
			throw new Brindille_Exception(sprintf('"%s": block closing does not match last block "%s" opened', $name . $params_str, $last));
		}

		$tpl->_pop();

		return '<?php } '
			// Prepend function name to error
			. 'catch (Brindille_Exception $e) { throw new Brindille_Exception(sprintf("Error in \'%s\' %s: %s", $name, $context, $e->getMessage())); } '
			// Always remove current context variables even if return was used (should not be necessary anymore) FIXME
			//. 'finally { array_pop($this->_variables); } '
			. '}); ?>';
	}

	static public function _debug(string $str): void
	{
		echo sprintf('<pre style="padding: 5px; margin: 5px; background: yellow; white-space: pre-wrap; color: #000">%s</pre>', htmlspecialchars($str));
	}

	static protected function cache(string $id, callable $callback)
	{
		if (!array_key_exists($id, self::$_cache)) {
			self::$_cache[$id] = $callback();
		}

		return self::$_cache[$id];
	}

	static public function call(array $params, UserTemplate $tpl, int $line): ?\Generator
	{
		if (empty($params['section'])) {
			throw new Brindille_Exception('Missing "section" parameter for "call" section');
		}

		$name = $params['section'];
		unset($params['section']);

		$r = $tpl->callUserFunction('section', $name, $params, $line);

		if (!is_iterable($r)) {
			return null;
		}

		foreach ($r as $key => $value) {
			if (is_array($value)) {
				yield $value;
			}
			else {
				yield compact('key', 'value');
			}
		}
	}

	static public function load(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (!array_key_exists('table', $params)) {
			return LegacySections::load($params, $tpl, $line);
		}

		$db = DB::getInstance();
		$module_name = $params['module'] ?? $tpl->module->name;
		$table = Modules::getModuleTableName($module_name, $params['table']);

		$params['where'] ??= '1';
		$params['select'] ??= '*';

		unset($params['table'], $params['module']);
		$params['tables'] = $table;

		return self::sql($params, $tpl, $line);
	}

	static public function list(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (!array_key_exists('table', $params)) {
			return LegacySections::list($params, $tpl, $line);
		}

		if (!$tpl->module) {
			throw new TemplateException('Module name could not be found');
		}

		$db = DB::getInstance();
		$module_name = $params['module'] ?? $tpl->module->name;
		$table = Modules::getModuleTableName($module_name, $params['table']);

		$params['where'] ??= '1';

		if (empty($params['columns'])
			|| !is_array($params['columns'])) {
			throw new TemplateException('Missing or invalid "columns" parameter');
		}

		$columns = $params['columns'];

		// Always select ID and key if not selected
		$columns['id'] ??= [];
		$columns['key'] ??= [];

		if (!empty($params['join'])) {
			$table .= ' ' . $params['join'];
		}

		$list = new DynamicList($columns, $table);

		// Make sure we cannot get unauthorized data
		$list->setAllowedTables(self::SQL_TABLES);

		static $reserved_keywords = ['table', 'max', 'order', 'desc', 'debug', 'explain', 'columns', 'where', 'module', 'user_sorting', 'checkable', 'group', 'export_button'];

		foreach ($params as $key => $value) {
			if ($key[0] == ':') {
				if (!str_contains($params['where'], $key)) {
					throw new TemplateException(sprintf('Parameter "%s" is not mentioned in "where" parameter', $key));
					$list->setParameter(substr($key, 1), $value);
				}
			}
			elseif (!in_array($key, $reserved_keywords, true)) {
				$hash = sha1($key);
				$params['where'] .= sprintf(' AND %s = :param_%s', $db->quoteIdentifier($key), $hash);
				$list->setParameter('param_' . $hash, $value);
			}
		}

		$list->setConditions($params['where']);
		$size = (int) ($params['max'] ?? 50);
		$list->setPageSize($size === 0 ? null : $size);

		if (isset($params['order'])) {
			$list->orderBy($params['order'], $params['desc'] ?? false);
		}

		if (isset($params['group'])) {
			$list->groupBy($params['group'], $table);
		}

		if ($params['user_sorting'] ?? true) {
			$list->loadFromQueryString();
		}

		if (!empty($params['debug'])) {
			self::_debug($list->SQL());
		}

		try {
			$i = $list->iterate();

			// If there is nothing to iterate, just stop
			if (!$i->valid()) {
				return;
			}
		}
		catch (DB_Exception $e) {
			throw new TemplateException(sprintf("Line %d: invalid SQL query: %s\nQuery: %s", $line, $e->getMessage(), $list->SQL()));
		}

		$tpl = new Template('common/dynamic_list_head.tpl', Template::getInstance());

		if (!empty($params['export_button'])) {
			$export_params = ['right' => true];

			printf('<p class="actions">%s</p>', CommonFunctions::exportmenu($export_params));
		}

		$tpl->assign(compact('list'));
		$tpl->assign('check', $params['checkable'] ?? false);
		$tpl->assign('disable_user_sort', boolval($params['user_sorting'] ?? false));
		$tpl->display();

		foreach ($i as $row) {
			yield (array) $row;
		}

		echo '</tbody>';
		echo '</table>';

		echo $list->getHTMLPagination();
	}

	static protected function getAccountCodeCondition($codes, string $column = 'code')
	{
		if (!is_array($codes)) {
			$codes = explode(',', $codes);
		}

		$db = DB::getInstance();

		foreach ($codes as &$code) {
			$code = $column . ' LIKE ' . $db->quote($code);
		}

		unset($code);

		return implode(' OR ', $codes);
	}

	static public function balances(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';
		$params['tables'] = 'acc_accounts_balances';

		if (isset($params['codes'])) {
			$params['where'] .= sprintf(' AND (%s)', self::getAccountCodeCondition($params['codes']));
			unset($params['codes']);
		}

		if (isset($params['year'])) {
			$params['where'] .= ' AND id_year = :year';
			$params[':year'] = $params['year'];
			unset($params['year']);
		}

		$params['select'] = $params['select'] ?? 'SUM(credit) AS credit, SUM(debit) AS debit, SUM(balance) AS balance, label, code';

		return self::sql($params, $tpl, $line);
	}

	static public function accounts(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$db = DB::getInstance();

		$params['where'] ??= '';
		$params['tables'] = 'acc_accounts';

		if (isset($params['codes'])) {
			$params['codes'] = explode(',', $params['codes']);

			foreach ($params['codes'] as &$code) {
				$code = 'code LIKE ' . $db->quote($code);
			}

			$params['where'] .= sprintf(' AND (%s)', implode(' OR ', $params['codes']));

			unset($code, $params['codes']);
		}
		elseif (isset($params['id'])) {
			$params['where'] .= ' AND id = :id';
			$params[':id'] = (int) $params['id'];
			unset($params['id']);
		}

		return self::sql($params, $tpl, $line);
	}

	static public function years(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['tables'] = 'acc_years';

		$params['where'] ??= '';

		if (isset($params['closed'])) {
			$params['where'] .= sprintf(' AND status = %d', $params['closed'] ? Year::CLOSED : Year::OPEN);
			unset($params['closed']);
		}

		return self::sql($params, $tpl, $line);
	}

	static public function projects(array $params, UserTemplate $tpl, int $line): ?\Generator
	{
		$params['tables'] = 'acc_projects';
		$params['archived'] ??= false;

		if (!empty($params['assign_list'])) {
			$list = [];
			$db = DB::getInstance();
			$sql = sprintf('SELECT id, label, code FROM %s WHERE archived = %d ORDER BY code, label COLLATE U_NOCASE;',
				$params['tables'],
				$params['archived']
			);

			foreach ($db->iterate($sql) as $row) {
				$label = '';

				if ($row->code) {
					$label = $row->code . ' — ';
				}

				$list[$row->id] = $label . $row->label;
			}

			$tpl->assign($params['assign_list'], $list);
			return null;
		}

		$params['where'] ??= '';

		$params['where'] .= sprintf(' AND archived = %d', $params['archived']);
		unset($params['archived']);

		return self::sql($params, $tpl, $line);
	}

	static public function users(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';

		$db = DB::getInstance();

		$id_field = DynamicFields::getNameFieldsSQL('u');
		$login_field = DynamicFields::getLoginField();
		$number_field = DynamicFields::getNumberField();
		$email_field = DynamicFields::getFirstEmailField();

		if (empty($params['select'])) {
			$params['select'] = 'u.*';
		}

		$params['select'] .= sprintf(', u.id AS id, %s AS _name, u.%s AS _login, u.%s AS _number, u.%s AS _email',
			$id_field,
			$db->quoteIdentifier($login_field),
			$db->quoteIdentifier($number_field),
			$db->quoteIdentifier($email_field)
		);

		$params['tables'] = 'users_view AS u';

		if (isset($params['id']) && is_array($params['id'])) {
			$params['id'] = array_map('intval', $params['id']);
			$params['where'] .= ' AND u.' . $db->where('id', $params['id']);
			unset($params['id']);
		}
		elseif (isset($params['id'])) {
			$params['where'] .= ' AND u.id = :id';
			$params[':id'] = (int) $params['id'];
			unset($params['id']);
		}
		elseif (isset($params['id_parent'])) {
			$params['where'] .= ' AND u.id_parent = :id_parent';
			$params[':id_parent'] = (int) $params['id_parent'];
			unset($params['id_parent']);
		}

		if (!empty($params['search_name'])) {
			$params['tables'] .= sprintf(' INNER JOIN users_search AS us ON us.id = u.id AND %s LIKE :search_name ESCAPE \'\\\' COLLATE NOCASE',
				DynamicFields::getNameFieldsSearchableSQL('us'));
			$params[':search_name'] = '%' . Utils::unicodeTransliterate($params['search_name']) . '%';
			unset($params['search_name']);
		}

		if (!empty($params['search'])) {
			if (!is_array($params['search'])) {
				throw new Brindille_Exception('Le paramètre "search" n\'est pas un tableau');
			}

			$params['tables'] .= ' INNER JOIN users_search AS us ON us.id = u.id';
			$i = 0;

			foreach ($params['search'] as $field => $value) {
				if ($field === '_email') {
					$params[':search_email'] = $value;
					$email_fields = DynamicFields::getEmailFields();
					$email_fields = array_map([$db, 'quoteIdentifier'], $email_fields);
					$email_fields = array_map(fn($a) => 'u.' . $a, $email_fields);
					$search[] = sprintf(':search_email IN (%s)', implode(', ', $email_fields));
				}
				elseif ($field === '_name' || $field === '_reversed_name') {
					$params[':search_name'] = Utils::unicodeTransliterate($value);
					$search[] = sprintf('%s = :search_name', DynamicFields::getNameFieldsSearchableSQL('us', $field === '_reversed_name'));
				}
				else {
					$params[':search_' . $i] = Utils::unicodeTransliterate($value);
					$search[] = sprintf('%s = :search_name', 'us.' . $db->quoteIdentifier($field));
				}

				$i++;
			}

			$params['where'] .= sprintf('(%s)', implode(' OR ', $search));

			unset($params['search']);
		}

		if (empty($params['order'])) {
			$params['order'] = 'u.id';
		}

		$files_fields = array_keys(DynamicFields::getInstance()->fieldsByType('file'));

		foreach (self::sql($params, $tpl, $line) as $row) {
			foreach ($row as $key => &$value) {
				if (in_array($key, $files_fields)) {
					$value = array_map(fn($a) => $a->export(), array_values(Files::list(File::CONTEXT_USER . '/' . $row['id'] . '/' . $key)));
				}
			}

			unset($value);

			yield $row;
		}
	}

	static public function subscriptions(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';

		$params['select'] = 'su.expiry_date, su.date, s.label, su.paid, su.expected_amount,
			CASE WHEN su.expiry_date >= date() THEN 1 WHEN su.expiry_date IS NOT NULL THEN -1 ELSE NULL END AS status';
		$params['tables'] = 'services_users su INNER JOIN services s ON s.id = su.id_service';

		if (isset($params['user'])) {
			$params['where'] .= ' AND su.id_user = :id_user';
			$params[':id_user'] = (int) $params['user'];
			unset($params['user']);
		}

		if (isset($params['id_service'])) {
			$params['where'] .= ' AND su.id_service = :id_service';
			$params[':id_service'] = (int) $params['id_service'];
			unset($params['id_service']);
		}

		if (isset($params['active'])) {
			if (!$params['active']) {
				$params['having'] = 'MAX(su.expiry_date) < date()';
			}
			else {
				$params['having'] = 'MAX(su.expiry_date) >= date()';
			}

			unset($params['active']);
		}
		elseif (!empty($params['by_service'])) {
			$params['select'] .= ', MAX(su.expiry_date) AS expiry_date';
			$params['group'] = 's.id';
		}

		// Hide archived subscriptions
		if (($params['archived'] ?? null) === false) {
			$params['where'] .= ' AND s.archived = 0';
		}

		if (empty($params['order'])) {
			$params['order'] = 'su.id';
		}

		$params['group'] = 'su.id_user, su.id_service';

		return self::sql($params, $tpl, $line);
	}

	static public function transactions(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$db = DB::getInstance();
		$params['where'] ??= '';

		$id_field = DynamicFields::getNameFieldsSQL();

		$params['select'] = sprintf('t.*, SUM(l.credit) AS credit, SUM(l.debit) AS debit,
			GROUP_CONCAT(DISTINCT a.code) AS accounts_codes,
			CASE WHEN t.type != 0 THEN l.reference ELSE NULL END AS payment_reference,
			(SELECT GROUP_CONCAT(DISTINCT %s) FROM users WHERE id IN (SELECT id_user FROM acc_transactions_users WHERE id_transaction = t.id)) AS users_names', $id_field);
		$params['tables'] = 'acc_transactions AS t
			INNER JOIN acc_transactions_lines AS l ON l.id_transaction = t.id
			INNER JOIN acc_accounts AS a ON l.id_account = a.id';
		$params['group'] = 't.id';

		if (isset($params['id']) && is_array($params['id'])) {
			$params['where'] .= ' AND t.' . $db->where('id', array_map('intval', $params['id']));
			unset($params['id']);
		}
		elseif (isset($params['id'])) {
			$params['where'] .= ' AND t.id = :id';
			$params[':id'] = (int) $params['id'];
			unset($params['id']);
		}
		elseif (isset($params['user'])) {
			$params['where'] .= ' AND t.id IN (SELECT id_transaction FROM acc_transactions_users WHERE id_user = :id_user)';
			$params[':id_user'] = (int) $params['user'];
			unset($params['user']);
		}

		if (isset($params['debit_codes'])) {
			$params['where'] .= sprintf(' AND l.credit = 0 AND (%s)', self::getAccountCodeCondition($params['debit_codes'], 'a.code'));
		}
		elseif (isset($params['credit_codes'])) {
			$params['where'] .= sprintf(' AND l.debit = 0 AND (%s)', self::getAccountCodeCondition($params['credit_codes'], 'a.code'));
		}

		unset($params['debit_codes'], $params['credit_codes']);

		if (isset($params['order']) && ctype_alpha(substr((string) $params['order'], 0, 1))) {
			$params['order'] = 't.' . $params['order'];
		}

		return self::sql($params, $tpl, $line);
	}

	static public function transaction_lines(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';

		if (isset($params['transaction'])) {
			$params['where'] .= ' AND l.id_transaction = :transaction';
			$params[':transaction'] = (int) $params['transaction'];
			unset($params['transaction']);
		}

		$params['select'] = 'l.*, a.code AS account_code, a.label AS account_label';
		$params['tables'] = 'acc_transactions_lines AS l
			INNER JOIN acc_accounts AS a ON l.id_account = a.id';

		return self::sql($params, $tpl, $line);
	}

	static public function transaction_users(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';

		if (isset($params['id_transaction'])) {
			$params['where'] = ' AND tu.id_transaction = :id_transaction';
			$params[':id_transaction'] = (int) $params['id_transaction'];
			unset($params['id_transaction']);
		}

		$id_field = DynamicFields::getNameFieldsSQL('u');
		$email_field = DB::getInstance()->quoteIdentifier(DynamicFields::getFirstEmailField());

		$params['select'] = sprintf('tu.*, %s AS name, %1$s AS _name, u.%s AS _email, u.*', $id_field, $email_field);
		$params['tables'] = 'acc_transactions_users tu
			INNER JOIN users u ON u.id = tu.id_user';

		return self::sql($params, $tpl, $line);
	}

	static public function restrict(array $params, UserTemplate $tpl, int $line): ?\Generator
	{
		$session = Session::getInstance();

		if (!$session->isLogged()) {
			if (!empty($params['block'])) {
				if (!headers_sent()) {
					Utils::redirect('!login.php?r=' . rawurlencode(Utils::getSelfURI()));
				}

				throw new UserException('Vous n\'avez pas accès à cette page.');
			}

			return null;
		}

		if (empty($params['level']) && empty($params['section'])) {
			yield [];
			return null;
		}

		$convert = [
			'none' => $session::ACCESS_NONE,
			'read' => $session::ACCESS_READ,
			'write' => $session::ACCESS_WRITE,
			'admin' => $session::ACCESS_ADMIN,
		];

		if (empty($params['level']) || !array_key_exists($params['level'], $convert)) {
			throw new Brindille_Exception(sprintf("Ligne %d: 'restrict' niveau d'accès inconnu : %s", $line, $params['level'] ?? ''));
		}

		if (empty($params['section']) || !in_array($params['section'], $session::SECTIONS)) {
			throw new Brindille_Exception(sprintf("Ligne %d: 'restrict' section d'accès inconnu : %s", $line, $params['section'] ?? ''));
		}

		$ok = $session->canAccess($params['section'], $convert[$params['level']]);

		if ($ok) {
			yield [];
			return null;
		}

		if (!empty($params['block'])) {
			throw new UserException('Vous n\'avez pas accès à cette page.');
		}

		return null;
	}

	static public function breadcrumbs(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (isset($params['id_page'])) {
			$id = (int) $params['id_page'];
		}
		elseif (isset($params['path']) || isset($params['uri'])) {
			$id = self::_getPageIdFromPath($params['path'] ?? $params['uri']);
		}
		else {
			throw new Brindille_Exception('"id_page", "uri" or "path" parameter is mandatory and is missing');
		}

		if (!$id) {
			return;
		}

		foreach (Web::getBreadcrumbs($id) as $row) {
			$row->url = '/' . $row->uri;
			yield (array) $row;
		}
	}

	static public function categories(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';
		$params['where'] .= ' AND w.type = ' . Page::TYPE_CATEGORY;
		return self::pages($params, $tpl, $line);
	}

	static public function articles(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';
		$params['where'] .= ' AND w.type = ' . Page::TYPE_PAGE;
		return self::pages($params, $tpl, $line);
	}

	static public function pages(array $params, UserTemplate $tpl, int $line): \Generator
	{
		static $listed = [];

		$params['where'] ??= '';
		$params['select'] = 'w.*';
		$params['tables'] = 'web_pages w';
		$params['where'] .= ' AND inherited_status != :status';
		$params[':status'] = Page::STATUS_DRAFT;

		if (empty($params['private']) && !Session::getInstance()->isLogged()) {
			$params['where'] .= ' AND inherited_status != :status2';
			$params[':status2'] = Page::STATUS_PRIVATE;
			unset($params['private']);
		}

		$allowed_tables = self::SQL_TABLES;

		if (array_key_exists('search', $params)) {
			if (trim((string) $params['search']) === '') {
				return;
			}

			$params[':search'] = substr(trim($params['search']), 0, 100);
			unset($params['search']);

			$params['tables'] .= ' INNER JOIN files_search ON files_search.path = \'web/\' || w.uri';
			$params['select'] .= ', rank(matchinfo(files_search), 0, 1.0, 1.0) AS points, snippet(files_search, \'<mark>\', \'</mark>\', \'…\', 2) AS snippet';
			$params['where'] .= ' AND files_search MATCH :search';

			$params['order'] = 'points DESC';
			$params['limit'] = '30';

			// There is a bug in SQLite3 < 3.41.0
			// where virtual tables (eg. FTS4) will trigger UPDATEs in the authorizer,
			// making the request fail.
			// So we will disable the authorizer here.
			// From a security POV, this is a compromise, but in PHP < 8 there was no authorizer
			// at all.
			// @see https://sqlite.org/forum/forumpost/e11b51ca555f82147a1cbb58dc640b441e5f126cf6d7400753f62e82ca11ba88
			if (\SQLite3::version()['versionNumber'] < 3041000) {
				$allowed_tables = null;
			}
		}

		if (isset($params['path'])) {
			$params['uri'] = Utils::basename($params['path']);
			unset($params['path']);
		}

		if (isset($params['uri'])) {
			$params['where'] .= ' AND w.uri = :uri';
			$params['limit'] = 1;
			$params[':uri'] = $params['uri'];
			unset($params['uri']);
		}

		if (array_key_exists('parent', $params)) {
			if (null === $params['parent']) {
				$params['where'] .= ' AND w.id_parent IS NULL';
			}
			else {
				if (substr($params['parent'], 0, 1) === '!') {
					$params['parent'] = substr($params['parent'], 1);
					$params['where'] .= ' AND w.id_parent != (SELECT id FROM web_pages WHERE uri = :parent)';
				}
				else {
					$params['where'] .= ' AND w.id_parent = (SELECT id FROM web_pages WHERE uri = :parent)';
				}

				$params[':parent'] = Utils::basename(trim((string) $params['parent']));
			}

			unset($params['parent']);
		}

		if (array_key_exists('id_parent', $params)) {
			if (null === $params['id_parent']) {
				$params['where'] .= ' AND w.id_parent IS NULL';
			}
			else {
				$params['where'] .= ' AND w.id_parent = :id_parent';
				$params[':id_parent'] = (int) $params['id_parent'];
			}

			unset($params['id_parent']);
		}

		if (isset($params['future'])) {
			$params['where'] .= sprintf(' AND w.published %s datetime(\'now\', \'localtime\')', $params['future'] ? '>' : '<=');
			unset($params['future']);
		}

		$assign = $params['assign'] ?? null;
		unset($params['assign']);

		if (empty($params['order'])) {
			$params['order'] = 'title';
		}

		if ($params['order'] == 'title') {
			$params['order'] .= ' COLLATE U_NOCASE';
		}

		if (!($params['duplicates'] ?? true)) {
			$params['where'] .= ' AND w.' . DB::getInstance()->where('id', 'NOT IN', $listed);
		}

		unset($params['duplicates']);

		foreach (self::sql($params, $tpl, $line, $allowed_tables) as $row) {
			if (empty($params['count'])) {
				$data = $row;
				unset($data['points'], $data['snippet']);

				$page = new Page;
				$page->exists(true);
				$page->load($data);
				$listed[] = $page->id;

				if (array_key_exists('snippet', $row)) {
					$row['snippet'] = preg_replace('!</mark>(\s*)<mark>!', '$1', $row['snippet'] ?? '');
					if (preg_match('!<mark>(.*?)</mark>!', $row['snippet'], $match)) {
						$row['url_highlight'] = $page->url() . '#:~:text=' . rawurlencode($match[1]);
					}
					else {
						$row['url_highlight'] = $page->url();
					}
				}

				$row = array_merge($row, $page->asTemplateArray());
			}

			if ($assign) {
				$tpl::_assign(['var' => $assign, 'value' => $row], $tpl, $line);
			}

			yield $row;
		}
	}

	static public function images(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';
		$params['where'] .= ' AND image = 1';
		return self::attachments($params, $tpl, $line);
	}

	static public function documents(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';
		$params['where'] .= ' AND image = 0';
		return self::attachments($params, $tpl, $line);
	}

	static protected function _getPageIdFromPath(string $path): ?int
	{
		return self::cache('page_id_' . md5($path), function () use ($path) {
			$db = DB::getInstance();
			return $db->firstColumn('SELECT id FROM web_pages WHERE uri = ?;', Utils::basename($path)) ?: null;
		});
	}

	static public function attachments(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (!empty($params['id_page'])) {
			$id = (int)$params['id_page'];
		}
		elseif (!empty($params['parent'])) {
			$id = self::_getPageIdFromPath($params['parent']);
		}
		else {
			throw new Brindille_Exception('La section "attachments" doit obligatoirement comporter un paramètre "id_page" ou "parent"');
		}

		if (!$id) {
			return;
		}

		$db = DB::getInstance();
		$params['where'] ??= '';

		// Fetch page
		$page = self::cache('page_' . $id, function () use ($id, $db) {
			$page = Web::get($id);

			if (!$page) {
				return null;
			}

			// Store attachments in temp table
			$db->begin();
			$db->exec('CREATE TEMP TABLE IF NOT EXISTS web_pages_attachments (page_id, uri, path, name, modified, image, data);');

			foreach ($page->listAttachments() as $file) {
				if ($file->type != File::TYPE_FILE) {
					continue;
				}

				$row = $file->asArray();
				$row['title'] = str_replace(['_', '-'], ' ', $file->name);
				$row['title'] = preg_replace('!\.[^\.]{3,5}$!', '', $row['title']);
				$row['extension'] = strtoupper(preg_replace('!^.*\.([^\.]{3,5})$!', '$1', $file->name));
				$row['format'] = $file->getFormatDescription();
				$row['url'] = $file->url();
				$row['download_url'] = $file->url(true);
				$row['thumb_url'] = $file->thumb_url();
				$row['small_url'] = $file->thumb_url(File::THUMB_SIZE_SMALL);
				$row['large_url'] = $file->thumb_url(File::THUMB_SIZE_LARGE);

				$db->preparedQuery('INSERT OR REPLACE INTO web_pages_attachments VALUES (?, ?, ?, ?, ?, ?, ?);',
					$page->id(), $file->uri(), $file->path, $file->name, $file->modified, $file->isImage(), json_encode($row));
			}

			$db->commit();

			return $page;
		});

		// Page not found
		if (!$page) {
			return;
		}

		$params['select'] = 'data';
		$params['tables'] = 'web_pages_attachments';
		$params['where'] .= ' AND page_id = :page';
		$params[':page'] = $page->id();
		unset($params['page']);

		// Generate a temporary table containing the list of files included in the text
		if (!empty($params['except_in_text'])) {
			// Don't regenerate that table for each section called in the page,
			// we assume the content and list of files will not change between sections
			self::cache('page_files_text_' . $id, function () use ($page, $db) {
				$db->begin();

				// Put files mentioned in the text in a temporary table
				$db->exec('CREATE TEMP TABLE IF NOT EXISTS files_tmp_in_text (page_id, uri);');

				foreach ($page->listTaggedAttachments() as $uri) {
					$db->insert('files_tmp_in_text', ['page_id' => $page->id(), 'uri' => $uri]);
				}


				$db->commit();
			});

			$params['where'] .= sprintf(' AND uri NOT IN (SELECT uri FROM files_tmp_in_text WHERE page_id = %d)', $page->id());
		}

		if (empty($params['order'])) {
			$params['order'] = 'name';
		}

		if ($params['order'] == 'name') {
			$params['order'] .= ' COLLATE U_NOCASE';
		}

		foreach (self::sql($params, $tpl, $line) as $row) {
			yield json_decode($row['data'], true);
		}
	}

	static public function module(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (empty($params['name'])) {
			throw new Brindille_Exception('Missing parameter "name"');
		}

		$module = Modules::get($params['name']);

		if (!$module || !$module->enabled) {
			return null;
		}

		$out = $module->asArray();
		$out['path'] = 'modules/' . $module->name;
		$out['url'] = $module->url();
		$out['public_url'] = $module->public_url();

		yield $out;
	}

	static public function extension(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (empty($params['name'])) {
			throw new Brindille_Exception('Missing parameter "name"');
		}

		$ext = Extensions::get($params['name']);

		if (!$ext || !$ext->enabled) {
			return null;
		}

		$out = $ext->asArray();
		$out['config'] = ($ext->module ?? $ext->plugin)->config;
		unset($out['module'], $out['plugin']);
		yield $out;
	}

	static public function files(array $params, UserTemplate $ut, int $line): \Generator
	{
		if (empty($ut->module)) {
			throw new Brindille_Exception('Module could not be found');
		}

		$path = $ut->module->storage_root();

		if (isset($params['path'])) {
			if (preg_match('!/\.|\.\.|//|\\\\!', $path)) {
				throw new Brindille_Exception(sprintf('"path" parameter is invalid: "%s"', $params['path']));
			}

			$path .= '/' . $params['path'];
		}

		$parent = Files::get($path);

		if (!$parent) {
			return null;
		}

		if (!empty($params['recursive'])) {
			$i = $parent->iterateRecursive();
		}
		else {
			$i = $parent->iterate();
		}

		foreach ($i as $file) {
			$extra = [
				'is_dir'        => $file->isDir(),
				'url'           => $file->url(),
				'download_url'  => $file->isDir() ? null : $file->url(true),
				'thumbnail_url' => $file->thumb_url(),
				'preview_html'  => $file->previewHTML(),
			];

			yield array_merge($file->asArray(), $extra);
		}
	}

	static public function sql(array $params, UserTemplate $tpl, int $line, ?array $allowed_tables = self::SQL_TABLES): \Generator
	{
		static $defaults = [
			'select' => '*',
			'order' => '1',
			'begin' => 0,
			'limit' => 10000,
			'where' => '',
		];

		if (isset($params['sql'])) {
			$sql = $params['sql'];

			// Replace raw SQL parameters (this is for #select section)
			foreach ($params as $k => $v) {
				if (substr($k, 0, 1) == '!') {
					$r = '/' . preg_quote($k, '/') . '\b/';
					$sql = preg_replace($r, (string)$v, $sql);
				}
			}
		}
		else {
			if (empty($params['tables'])) {
				throw new Brindille_Exception(sprintf('"sql" section: missing parameter "tables" on line %d', $line));
			}

			foreach ($defaults as $key => $default_value) {
				if (!isset($params[$key])) {
					$params[$key] = $default_value;
				}
			}

			$group = '';

			if (isset($params['group'])) {
				$group .= 'GROUP BY ' . $params['group'];
			}

			if (isset($params['having'])) {
				$group .= ' HAVING ' . $params['having'];
			}

			if (!empty($params['where']) && !preg_match('/^\s*AND\s+/i', $params['where'])) {
				$params['where'] = ' AND ' . $params['where'];
			}

			// If count=true or count=1, then wrap query and return count
			if (!empty($params['count'])) {
				$sql = sprintf('SELECT COUNT(*) AS count FROM (SELECT 1 FROM %s WHERE 1 %s %s);',
					$params['tables'],
					$params['where'] ?? '',
					$group
				);
			}
			else {
				$sql = sprintf('SELECT %s FROM %s WHERE 1 %s %s ORDER BY %s LIMIT %d,%d;',
					$params['select'],
					$params['tables'],
					$params['where'] ?? '',
					$group,
					$params['order'],
					$params['begin'],
					$params['limit']
				);
			}
		}

		if (isset($tpl->module->name)) {
			$name = $tpl->module->name;
			$table = $tpl->module->data_table_name();
			$sql = LegacySections::_moduleReplaceJSONExtract($sql, $table); // FIXME: remove in Paheko 1.5+ (?)
		}

		$db = DB::getInstance();

		try {
			// Lock database against changes
			$db->setReadOnly(true);

			$statement = $db->prepareRestricted($allowed_tables, $sql);

			$args = [];

			foreach ($params as $key => $value) {
				if (substr($key, 0, 1) == ':') {
					if (is_object($value) || is_array($value)) {
						throw new Brindille_Exception(sprintf("à la ligne %d : Section 'sql': le paramètre '%s' est un tableau.", $line, $key));
					}

					$args[substr($key, 1)] = $value;
				}
			}

			$result = $db->execute($statement, $args);

			if (!empty($params['debug'])) {
				self::_debug($statement->getSQL(true));
			}

			$db->setReadOnly(false);
		}
		catch (DB_Exception $e) {
			if (strpos($e->getMessage(), 'malformed MATCH') !== false) {
				throw new UserException('Motif de recherche invalide', 0, $e);
			}

			throw new Brindille_Exception(sprintf("à la ligne %d erreur SQL :\n%s\n\nRequête exécutée :\n%s", $line, $e->getMessage(), $sql));
		}

		while ($row = $result->fetchArray(\SQLITE3_ASSOC))
		{
			if (isset($params['assign'])) {
				$tpl::_assign(['var' => $params['assign'], 'value' => $row], $tpl, $line);
			}

			yield $row;
		}
	}
}
