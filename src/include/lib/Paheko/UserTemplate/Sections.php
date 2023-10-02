<?php

namespace Paheko\UserTemplate;

use KD2\Brindille_Exception;
use KD2\DB\DB_Exception;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Template;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\Users\Session;
use Paheko\Entities\Web\Page;
use Paheko\Web\Web;
use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Users\DynamicFields;

class Sections
{
	const SECTIONS_LIST = [
		'load',
		'list',
		'categories',
		'articles',
		'pages',
		'images',
		'breadcrumbs',
		'documents',
		'files',
		'users',
		'subscriptions',
		'transactions',
		'transaction_lines',
		'transaction_users',
		'accounts',
		'balances',
		'years',
		'sql',
		'restrict',
		'module',
	];

	const COMPILE_SECTIONS_LIST = [
		'#select' => [self::class, 'selectStart'],
		'/select' => [self::class, 'selectEnd'],
		'#form'    => [self::class, 'formStart'],
		'/form'    => [self::class, 'formEnd'],
		'else:form'    => [self::class, 'formElse'],
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
		'users' => ['~password', '~pgp_key', '~otp_secret'],
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
		$extra_params = strtok(false);

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
		$tpl->_push($tpl::SECTION, 'form');

		$params = $tpl->_parseArguments($params_str, $line);

		if (isset($params['on'])
			&& ($on = $tpl->getValueFromArgument($params['on']))
			&& preg_match('/^[a-z0-9-]+$/i', $on)) {
			$if = sprintf('$_POST[%s]', var_export($on, true));
		}
		else {
			$if = '$_POST';
		}

		unset($params['on']);
		$params = $tpl->_exportArguments($params);

		return sprintf('<?php if (!empty(%s)): ', $if)
			. 'try { '
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
			. 'endforeach; } catch (\Paheko\UserException $e) { '
			. '$this->assign(\'form_errors\', [$e->getMessage()]); '
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

		$out .= '<?php } endif; ?>';

		$out = str_replace(' ?><?php ', ' ', $out);

		return $out;
	}

	static protected function _debug(string $str): void
	{
		echo sprintf('<pre style="padding: 5px; margin: 5px; background: yellow; white-space: pre-wrap; color: #000">%s</pre>', htmlspecialchars($str));
	}

	static protected function _debugExplain(string $sql): void
	{
		$explain = '';

		try {
			$r = DB::getInstance()->get('EXPLAIN QUERY PLAN ' . $sql);

			foreach ($r as $e) {
				$explain .= $e->detail . "\n";
			}
		}
		catch (DB_Exception $e) {
			$explain = 'Error: ' . $e->getMessage();
		}

		self::_debug($explain);
	}

	static protected function cache(string $id, callable $callback)
	{
		if (!array_key_exists($id, self::$_cache)) {
			self::$_cache[$id] = $callback();
		}

		return self::$_cache[$id];
	}

	/**
	 * Creates indexes for json_extract expressions
	 */
	static protected function _createModuleIndexes(string $table, string $where): void
	{
		preg_match_all('/json_extract\s*\(\s*document\s*,\s*(?:\'(.*?)\'|\"(.*?)\")\s*\)/', $where, $match, PREG_SET_ORDER);

		if (!count($match)) {
			return;
		}

		$search_params = [];

		foreach ($match as $m) {
			$search_params[$m[2] ?? $m[1]] = $m[0];
		}

		if (!count($search_params)) {
			return;
		}

		ksort($search_params);
		$hash = sha1(implode('', array_keys($search_params)));

		$db = DB::getInstance();

		try {
			$db->exec(sprintf('CREATE INDEX IF NOT EXISTS %s_auto_%s ON %1$s (%s);', $table, $hash, implode(', ', $search_params)));
		}
		catch (DB_Exception $e) {
			throw new Brindille_Exception(sprintf("à la ligne %d, impossible de créer l'index, erreur SQL :\n%s\n\nRequête exécutée :\n%s", $line, $db->lastErrorMsg(), $sql));
		}
	}

	static public function load(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$name = $params['module'] ?? ($tpl->module->name ?? null);

		if (!$name) {
			throw new Brindille_Exception('Unique module name could not be found');
		}

		$table = 'module_data_' . $name;
		$params['tables'] = $table;

		$db = DB::getInstance();
		$has_table = $db->test('sqlite_master', 'type = \'table\' AND name = ?', $table);

		if (!$has_table) {
			return;
		}

		$delete_table = null;

		// Cannot use json_each with authorizer before SQLite 3.41.0
		// @see https://sqlite.org/forum/forumpost/d28110be11
		if (isset($params['each']) && !$db->hasFeatures('json_each_readonly')) {
			$t = 'module_tmp_each' . md5($params['each']);

			// We create a temporary table, to get around authorizer issues in SQLite
			$db->exec(sprintf('DROP TABLE IF EXISTS %s; CREATE TEMP TABLE IF NOT EXISTS %1$s (id, key, value, document);', $t));
			$db->exec(sprintf('INSERT INTO %s SELECT a.id, a.key, value, a.document FROM %s AS a, json_each(a.document, %s);',
				$t, $table, $db->quote('$.' . trim($params['each']))
			));

			$params['tables'] = $t;
			$params['select'] = 'value';
			unset($params['each']);
		}
		elseif (isset($params['each'])) {
			$params['tables'] = sprintf('%s AS a, json_each(a.document, %s)', $table, $db->quote('$.' . trim($params['each'])));
			unset($params['each']);
		}

		if (!isset($params['where'])) {
			$params['where'] = '1';
		}
		else {
			$params['where'] = self::_moduleReplaceJSONExtract($params['where']);
		}

		if (isset($params['key'])) {
			$params['where'] .= ' AND key = :key';
			$params['limit'] = 1;
			$params[':key'] = $params['key'];
			unset($params['key']);
		}
		elseif (isset($params['id'])) {
			$params['where'] .= ' AND id = :id';
			$params['limit'] = 1;
			$params[':id'] = $params['id'];
			unset($params['id']);
		}

		// Replace '$.name = "value"' parameters with json_extract
		foreach ($params as $key => $value) {
			$k = substr($key, 0, 1);
			if ($k == ':' || in_array($key, self::SQL_RESERVED_PARAMS)) {
				continue;
			}

			if (is_bool($value)) {
				$v = '= ' . (int) $value;
			}
			elseif (null === $value) {
				$v = 'IS NULL';
			}
			else {
				$v = sprintf(':quick_%s', sha1($key));
				$params[$v] = $value;
				$v = '= ' . $v;
			}

			$params['where'] .= sprintf(' AND json_extract(document, %s) %s', $db->quote('$.' . $key), $v);
			unset($params[$key]);
		}

		$s = 'id, key, document AS json';

		if (isset($params['select'])) {
			$params['select'] = $s . ', ' . self::_moduleReplaceJSONExtract($params['select']);
		}
		else {
			$params['select'] = $s;
		}

		if (isset($params['group'])) {
			$params['group'] = self::_moduleReplaceJSONExtract($params['group']);
		}

		if (isset($params['having'])) {
			$params['having'] = self::_moduleReplaceJSONExtract($params['having']);
		}

		if (isset($params['order'])) {
			$params['order'] = self::_moduleReplaceJSONExtract($params['order']);
		}

		// Try to create an index if required
		self::_createModuleIndexes($table, $params['where']);

		$query = self::sql($params, $tpl, $line);

		foreach ($query as $row) {
			if (isset($row['json'])) {
				$json = json_decode($row['json'], true);

				if (is_array($json)) {
					unset($row['json']);
					$row = array_merge($row, $json);
				}
			}

			if (isset($params['assign'])) {
				$tpl::__assign(['var' => $params['assign'], 'value' => $row], $tpl, $line);
			}

			yield $row;
		}
	}

	static protected function _getModuleColumnsFromSchema(string $schema, ?string $columns, UserTemplate $tpl, int $line): array
	{
		$schema = Functions::read(['file' => $schema], $tpl, $line);
		$schema = json_decode($schema, true);

		if (!$schema) {
			throw new Brindille_Exception(sprintf("ligne %d: impossible de lire le schéma:\n%s",
				$line, json_last_error_msg()));
		}

		if (empty($schema['properties'])) {
			return [];
		}

		$out = [];

		if (null !== $columns) {
			$columns = explode(',', $columns);
			$columns = array_map('trim', $columns);
		}
		else {
			$columns = array_keys($schema['properties']);
		}

		foreach ($columns as $key) {
			$rule = $schema['properties'][$key] ?? null;

			// This column is not in the schema
			if (!$rule) {
				continue;
			}

			$types = is_array($rule['type']) ? $rule['type'] : [$rule['type']];

			// Only "simple" types are supported
			if (in_array('array', $types) || in_array('object', $types)) {
				continue;
			}

			$out[$key] = [
				'label' => $rule['description'] ?? null,
				'select' => sprintf('json_extract(document, \'$.%s\')', $key),
			];
		}

		return $out;
	}

	static public function _moduleReplaceJSONExtract(string $str): string
	{
		if (!strstr($str, '$')) {
			return $str;
		}

		return preg_replace_callback(
			'/\$(\$[\[\.][\w\d\.\[\]#]+)/',
			fn ($m) => sprintf('json_extract(document, %s)', DB::getInstance()->quote($m[1])),
			$str
		);
	}

	static public function list(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (empty($params['schema']) && empty($params['select'])) {
			throw new Brindille_Exception('Missing schema parameter');
		}

		$name = $params['module'] ?? ($tpl->module->name ?? null);

		if (!$name) {
			throw new Brindille_Exception('Unique module name could not be found');
		}

		$table = 'module_data_' . $name;

		$db = DB::getInstance();
		$has_table = $db->test('sqlite_master', 'type = \'table\' AND name = ?', $table);

		if (!$has_table) {
			return;
		}

		if (!isset($params['where'])) {
			$where = '1';
		}
		else {
			$where = self::_moduleReplaceJSONExtract($params['where']);
		}

		$columns = [];

		if (!empty($params['select'])) {
			foreach (explode(';', $params['select']) as $i => $c) {
				$c = trim($c);

				$pos = strpos($c, ' AS ');

				if ($pos) {
					$select = trim(substr($c, 0, $pos));
					$label = str_replace("''", "'", trim(substr($c, $pos + 5), ' \'"'));
				}
				else {
					$select = $c;
					$label = null;
				}

				if ($select === '*') {
					throw new Brindille_Exception(sprintf('Line %d: "*" cannot be used in "select" parameter', $line));
				}

				$select = self::_moduleReplaceJSONExtract($select);

				$columns['col' . ($i + 1)] = compact('label', 'select');
			}

			if (isset($params['order'])) {
				if (!is_int($params['order']) && !ctype_digit($params['order'])) {
					throw new Brindille_Exception(sprintf('Line %d: "order" parameter must be the number of the column (starting from 1)', $line));
				}

				$params['order'] = 'col' . (int)$params['order'];
			}
		}
		else {
			$columns = self::_getModuleColumnsFromSchema($params['schema'], $params['columns'] ?? null, $tpl, $line);
		}

		$columns['id'] = [];
		$columns['key'] = [];
		$columns['document'] = [];

		$list = new DynamicList($columns, $table);

		static $reserved_keywords = ['max', 'order', 'desc', 'debug', 'explain', 'schema', 'columns', 'select', 'where', 'module'];

		foreach ($params as $key => $value) {
			if ($key[0] == ':') {
				if (false !== strpos($where, $key)) {
					$list->setParameter(substr($key, 1), $value);
				}
			}
			elseif (!in_array($key, $reserved_keywords)) {
				$hash = sha1($key);
				$where .= sprintf(' AND json_extract(document, %s) = :quick_%s', $db->quote('$.' . $key), $hash);
				$list->setParameter('quick_' . $hash, $value);
			}
		}

		$list->setConditions($where);
		$list->setPageSize((int) ($params['max'] ?? 50));

		if (isset($params['order'])) {
			$list->orderBy($params['order'], $params['desc'] ?? false);
		}

		$list->setModifier(function(&$row) {
			$row->original = clone $row;
			unset($row->original->id, $row->original->key, $row->original->document);

			if (null !== $row->document) {
				$row = array_merge(json_decode($row->document, true), (array)$row);
			}
			else {
				$row = (array) $row;
			}
		});

		$list->setExportCallback(function(&$row) {
			$row = $row['original'];
		});

		// Try to create an index if required
		self::_createModuleIndexes($table, $where);

		$list->loadFromQueryString();

		if (!empty($params['debug'])) {
			self::_debug($list->SQL());
		}

		if (!empty($params['explain'])) {
			self::_debugExplain($list->SQL());
		}

		$i = $list->iterate();

		// If there is nothing to iterate, just stop
		if (!$i->valid()) {
			return;
		}

		$tpl = Template::getInstance();

		/*
		FIXME: Export is broken currently
		$export_url = Utils::getSelfURI();
		$export_url .= strstr($export_url, '?') ? '&export=' : '?export=';
		printf('<p class="actions">%s</p>', $tpl->widgetExportMenu(['href' => $export_url, 'class' => 'menu-btn-right']));
		*/

		$tpl->assign(compact('list'));
		$tpl->assign('check', $params['check'] ?? false);
		$tpl->display('common/dynamic_list_head.tpl');

		yield from $i;

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
		$db = DB::getInstance();

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
			$params['where'] .= sprintf(' AND closed = %d', $params['closed']);
			unset($params['closed']);
		}

		return self::sql($params, $tpl, $line);
	}

	static public function users(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';

		$db = DB::getInstance();

		$id_field = DynamicFields::getNameFieldsSQL('users');
		$login_field = DynamicFields::getLoginField();
		$number_field = DynamicFields::getNumberField();
		$email_field = DynamicFields::getFirstEmailField();

		if (empty($params['select'])) {
			$params['select'] = 'users.*';
		}

		$params['select'] .= sprintf(', users.id AS id, %s AS _name, users.%s AS _login, users.%s AS _number, users.%s AS _email',
			$id_field,
			$db->quoteIdentifier($login_field),
			$db->quoteIdentifier($number_field),
			$db->quoteIdentifier($email_field)
		);

		$params['tables'] = 'users';

		if (isset($params['id']) && is_array($params['id'])) {
			$params['id'] = array_map('intval', $params['id']);
			$params['where'] .= ' AND users.' . $db->where('id', $params['id']);
			unset($params['id']);
		}
		elseif (isset($params['id'])) {
			$params['where'] .= ' AND users.id = :id';
			$params[':id'] = (int) $params['id'];
			unset($params['id']);
		}
		elseif (isset($params['id_parent'])) {
			$params['where'] .= ' AND users.id_parent = :id_parent';
			$params[':id_parent'] = (int) $params['id_parent'];
			unset($params['id_parent']);
		}

		if (!empty($params['search_name'])) {
			$params['tables'] .= sprintf(' INNER JOIN users_search AS us ON us.id = users.id AND %s LIKE :search_name ESCAPE \'\\\' COLLATE NOCASE',
				DynamicFields::getNameFieldsSearchableSQL('us'));
			$params[':search_name'] = '%' . Utils::unicodeTransliterate($params['search_name']) . '%';
			unset($params['search_name']);
		}

		if (empty($params['order'])) {
			$params['order'] = 'users.id';
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

		$number_field = DynamicFields::getNumberField();

		$params['select'] = sprintf('su.expiry_date, su.date, s.label, su.paid, su.expected_amount');
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

		if (!empty($params['active'])) {
			$params['having'] = 'MAX(su.expiry_date) >= date()';
			unset($params['active']);
		}

		if (empty($params['order'])) {
			$params['order'] = 'su.id';
		}

		$params['group'] = 'su.id_user, su.id_service';

		return self::sql($params, $tpl, $line);
	}

	static public function transactions(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';

		$id_field = DynamicFields::getNameFieldsSQL();

		$params['select'] = sprintf('t.*, SUM(l.credit) AS credit, SUM(l.debit) AS debit,
			GROUP_CONCAT(DISTINCT a.code) AS accounts_codes,
			(SELECT GROUP_CONCAT(DISTINCT %s) FROM users WHERE id IN (SELECT id_user FROM acc_transactions_users WHERE id_transaction = t.id)) AS users_names', $id_field);
		$params['tables'] = 'acc_transactions AS t
			INNER JOIN acc_transactions_lines AS l ON l.id_transaction = t.id
			INNER JOIN acc_accounts AS a ON l.id_account = a.id';
		$params['group'] = 't.id';

		if (isset($params['id'])) {
			$params['where'] .= ' AND t.id = :id';
			$params[':id'] = (int) $params['id'];
			unset($params['id']);
		}
		elseif (isset($params['user'])) {
			$params['where'] .= ' AND tu.id_user = :id_user';
			$params[':id_user'] = (int) $params['user'];
			unset($params['user']);

			$params['tables'] .= ' INNER JOIN acc_transactions_users AS tu ON tu.id_transaction = t.id';
		}

		if (isset($params['debit_codes'])) {
			$params['where'] .= sprintf(' AND l.debit > 0 AND (%s)', self::getAccountCodeCondition($params['debit_codes'], 'a.code'));
		}
		elseif (isset($params['credit_codes'])) {
			$params['where'] .= sprintf(' AND l.debit > 0 AND (%s)', self::getAccountCodeCondition($params['credit_codes'], 'a.code'));
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

		$id_field = DynamicFields::getNameFieldsSQL('u');

		$params['select'] = sprintf('l.*, a.code AS account_code, a.label AS account_label');
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
					// FIXME: implement redirect to correct URL after login
					Utils::redirect('!login.php');
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
			'read' => $session::ACCESS_READ,
			'write' => $session::ACCESS_WRITE,
			'admin' => $session::ACCESS_ADMIN,
		];

		if (empty($params['level']) || !isset($convert[$params['level']])) {
			throw new Brindille_Exception(sprintf("Ligne %d: 'restrict' niveau d'accès inconnu : %s", $line, $params['level'] ?? ''));
		}

		$ok = $session->canAccess($params['section'] ?? '', $convert[$params['level']]);

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
		$params['where'] ??= '';
		$params['select'] = 'w.*';
		$params['tables'] = 'web_pages w';
		$params['where'] .= ' AND status = :status';
		$params[':status'] = Page::STATUS_ONLINE;

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

		foreach (self::sql($params, $tpl, $line, $allowed_tables) as $row) {
			if (empty($params['count'])) {
				$data = $row;
				unset($data['points'], $data['snippet']);

				$page = new Page;
				$page->exists(true);
				$page->load($data);

				if (isset($row['snippet'])) {
					$row['snippet'] = preg_replace('!</mark>(\s*)<mark>!', '$1', $row['snippet']);
					if (preg_match('!<mark>(.*?)</mark>!', $row['snippet'], $match)) {
						$row['url_highlight'] = $page->url() . '#:~:text=' . rawurlencode($match[1]);
					}
					else {
						$row['url_highlight'] = $page->url();
					}
				}

				$row = array_merge($row, $page->asTemplateArray());
			}

			yield $row;
		}
	}

	static public function images(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';
		$params['where'] .= ' AND image = 1';
		return self::files($params, $tpl, $line);
	}

	static public function documents(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$params['where'] ??= '';
		$params['where'] .= ' AND image = 0';
		return self::files($params, $tpl, $line);
	}

	static protected function _getPageIdFromPath(string $path): ?int
	{
		return self::cache('page_id_' . md5($path), function () use ($path) {
			$db = DB::getInstance();
			return $db->firstColumn('SELECT id FROM web_pages WHERE uri = ?;', Utils::basename($path)) ?: null;
		});
	}

	static public function files(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$id = null;

		if (!empty($params['id_page'])) {
			$id = (int)$params['id_page'];
		}
		elseif (!empty($params['parent'])) {
			$id = self::_getPageIdFromPath($params['parent']);
		}
		else {
			throw new Brindille_Exception('La section "files" doit obligatoirement comporter un paramètre "id_page" ou "parent"');
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

	static public function sql(array $params, UserTemplate $tpl, int $line, ?array $allowed_tables = self::SQL_TABLES): \Generator
	{
		static $defaults = [
			'select' => '*',
			'order' => '1',
			'begin' => 0,
			'limit' => 100,
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

			// Allow for count=true, count=1 and also count="DISTINCT user_id" count="id"
			if (!empty($params['count'])) {
				$params['select'] = sprintf('COUNT(%s) AS count', $params['count'] == 1 ? '*' : $params['count']);
				$params['order'] = '1';
			}

			if (!empty($params['where']) && !preg_match('/^\s*AND\s+/i', $params['where'])) {
				$params['where'] = ' AND ' . $params['where'];
			}

			$sql = sprintf('SELECT %s FROM %s WHERE 1 %s %s %s ORDER BY %s LIMIT %d,%d;',
				$params['select'],
				$params['tables'],
				$params['where'] ?? '',
				isset($params['group']) ? 'GROUP BY ' . $params['group'] : '',
				isset($params['having']) ? 'HAVING ' . $params['having'] : '',
				$params['order'],
				$params['begin'],
				$params['limit']
			);
		}

		$db = DB::getInstance();

		try {
			// Lock database against changes
			$db->setReadOnly(true);

			$statement = $db->protectSelect($allowed_tables, $sql);

			$args = [];

			foreach ($params as $key => $value) {
				if (substr($key, 0, 1) == ':') {
					if (is_object($value) || is_array($value)) {
						throw new Brindille_Exception(sprintf("à la ligne %d : Section 'sql': le paramètre '%s' est un tableau.", $line, $key));
					}

					$args[$key] = $value;
				}
			}

			if (!empty($params['debug'])) {
				self::_debug($statement->getSQL(true));
			}

			if (!empty($params['explain'])) {
				self::_debugExplain($statement->getSQL(true));
			}

			$result = $db->execute($statement, $args);
			$db->setReadOnly(false);
		}
		catch (DB_Exception $e) {
			throw new Brindille_Exception(sprintf("à la ligne %d erreur SQL :\n%s\n\nRequête exécutée :\n%s", $line, $db->lastErrorMsg(), $sql));
		}

		while ($row = $result->fetchArray(\SQLITE3_ASSOC))
		{
			if (isset($params['assign'])) {
				$tpl::__assign(['var' => $params['assign'], 'value' => $row], $tpl, $line);
			}

			yield $row;
		}
	}
}
