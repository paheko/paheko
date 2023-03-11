<?php

namespace Garradin\UserTemplate;

use KD2\Brindille_Exception;
use Garradin\DB;
use Garradin\DynamicList;
use Garradin\Template;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Users\Session;
use Garradin\Entities\Web\Page;
use Garradin\Web\Web;
use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\Users\DynamicFields;

use const Garradin\WWW_URL;

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
		'sql',
		'restrict',
		'module',
	];

	const COMPILE_SECTIONS_LIST = [
		'#select' => [self::class, 'selectStart'],
		'/select' => [self::class, 'selectEnd'],
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
		catch (\KD2\DB\DB_Exception $e) {
			throw new Brindille_Exception(sprintf("à la ligne %d, impossible de créer l'index, erreur SQL :\n%s\n\nRequête exécutée :\n%s", $line, $db->lastErrorMsg(), $sql));
		}
	}

	static public function load(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$name = $params['module'] ?? Utils::basename(Utils::dirname($tpl->_tpl_path));

		if (!$name) {
			throw new Brindille_Exception('Unique module name could not be found');
		}

		$params['tables'] = 'module_data_' . $name;

		$db = DB::getInstance();
		$has_table = $db->test('sqlite_master', 'type = \'table\' AND name = ?', $params['tables']);

		if (!$has_table) {
			return;
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
			if (substr($key, 0, 1) != '$') {
				continue;
			}

			$hash = sha1($key);
			$params['where'] .= sprintf(' AND json_extract(document, %s) = :quick_%s', $db->quote($key), $hash);
			$params[':quick_' . $hash] = $value;
			unset($params[$key]);
		}

		$params['select'] = isset($params['select']) ? self::_moduleReplaceJSONExtract($params['select']) : 'id, key, document AS json';

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
		self::_createModuleIndexes($params['tables'], $params['where']);

		$query = self::sql($params, $tpl, $line);

		foreach ($query as $row) {
			if (isset($row['json'])) {
				$json = json_decode($row['json'], true);

				if (is_array($json)) {
					unset($row['json']);
					$row = array_merge($row, $json);
				}
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

		$name = $params['module'] ?? Utils::basename(Utils::dirname($tpl->_tpl_path));

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

				$select = self::_moduleReplaceJSONExtract($select);

				$columns['col' . ($i + 1)] = compact('label', 'select');
			}

			if (isset($params['order'])) {
				if (!is_int($params['order']) && !ctype_digit($params['order'])) {
					throw new Brindille_Exception(sprintf('Line %d: "order" parameter must be the number of the column (starting from zero)', $line));
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

		foreach ($params as $key => $value) {
			$f = substr($key, 0, 1);

			if ($f == ':' && strstr($where, $key)) {
				$list->setParameter(substr($key, 1), $value);
			}
			elseif ($f == '$') {
				// Replace '$.name = "value"' parameters with json_extract
				$hash = sha1($key);
				$where .= sprintf(' AND json_extract(document, %s) = :quick_%s', $db->quote($key), $hash);
				$list->setParameter('quick_' . $hash, $value);
			}
		}

		$list->setConditions($where);
		$list->setPageSize((int) ($params['max'] ?? 50));

		if (isset($params['order'])) {
			$list->orderBy($params['order'], $params['desc'] ?? false);
		}

		$list->setModifier(function(&$row) {
			//$row->original = clone $row;
			$row = array_merge(json_decode($row->document, true), (array)$row);
		});

		$list->setExportCallback(function(&$row) {
			//$row = $row['original'];
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

		yield from $list->iterate();

		echo '</tbody>';
		echo '</table>';


		echo $list->getHTMLPagination();
	}

	static public function balances(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$db = DB::getInstance();

		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['tables'] = 'acc_accounts_balances';

		if (isset($params['codes'])) {
			$params['codes'] = explode(',', $params['codes']);

			foreach ($params['codes'] as &$code) {
				$code = 'code LIKE ' . $db->quote($code);
			}

			$params['where'] .= sprintf(' AND (%s)', implode(' OR ', $params['codes']));

			unset($code, $params['codes']);
		}

		if (isset($params['year'])) {
			$params['where'] .= ' AND id_year = :year';
			$params[':year'] = $params['year'];
			unset($params['year']);
		}

		$params['select'] = $params['select'] ?? 'SUM(credit) AS credit, SUM(debit) AS debit, SUM(balance) AS balance, label, code';

		foreach (self::sql($params, $tpl, $line) as $row) {
			yield $row;
		}
	}

	static public function accounts(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$db = DB::getInstance();

		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

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

		foreach (self::sql($params, $tpl, $line) as $row) {
			yield $row;
		}
	}

	static public function users(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$id_field = DynamicFields::getNameFieldsSQL();
		$login_field = DynamicFields::getLoginField();
		$number_field = DynamicFields::getNumberField();

		if (empty($params['select'])) {
			$params['select'] = '*';
		}

		$params['select'] .= sprintf(', %s AS _name, %s AS _login, %s AS _number',
			$id_field, $login_field, $number_field);
		$params['tables'] = 'users';

		if (isset($params['id'])) {
			$params['where'] .= ' AND id = :id';
			$params[':id'] = (int) $params['id'];
			unset($params['id']);
		}

		if (empty($params['order'])) {
			$params['order'] = 'id';
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
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$number_field = DynamicFields::getNumberField();

		$params['select'] = sprintf('su.expiry_date, su.date, s.label, su.paid, su.expected_amount');
		$params['tables'] = 'services_users su INNER JOIN services s ON s.id = su.id_service';

		if (isset($params['user'])) {
			$params['where'] .= ' AND su.id_user = :id_user';
			$params[':id_user'] = (int) $params['user'];
			unset($params['user']);
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
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		if (isset($params['id'])) {
			$params['where'] .= ' AND t.id = :id';
			$params[':id'] = (int) $params['id'];
			unset($params['id']);
		}

		$id_field = DynamicFields::getNameFieldsSQL();

		$params['select'] = sprintf('t.*, SUM(l.credit) AS credit, SUM(l.debit) AS debit,
			GROUP_CONCAT(DISTINCT a.code) AS accounts_codes,
			(SELECT GROUP_CONCAT(DISTINCT %s) FROM users WHERE id IN (SELECT id_user FROM acc_transactions_users WHERE id_transaction = t.id)) AS users_names', $id_field);
		$params['tables'] = 'acc_transactions AS t
			INNER JOIN acc_transactions_lines AS l ON l.id_transaction = t.id
			INNER JOIN acc_accounts AS a ON l.id_account = a.id';
		$params['group'] = 't.id';

		return self::sql($params, $tpl, $line);
	}

	static public function transaction_lines(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

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
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		if (isset($params['id_transaction'])) {
			$params['where'] = ' AND tu.id_transaction = :id_transaction';
			$params[':id_transaction'] = (int) $params['id_transaction'];
			unset($params['id_transaction']);
		}

		$id_field = DynamicFields::getNameFieldsSQL('u');

		$params['select'] = sprintf('tu.*, %s AS name, u.*', $id_field);
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
		if (!isset($params['path'])) {
			throw new Brindille_Exception('"path" parameter is mandatory and is missing');
		}

		$paths = [];
		$path = '';

		foreach (explode('/', $params['path']) as $part) {
			$path = trim($path . '/' . $part, '/');
			$paths[$path] = null;
		}

		$db = DB::getInstance();
		$sql = sprintf('SELECT path, title FROM web_pages WHERE %s ORDER BY path ASC;', $db->where('path', array_keys($paths)));

		$result = $db->preparedQuery($sql);

		while ($row = $result->fetchArray(\SQLITE3_ASSOC))
		{
			$row['url'] = WWW_URL . Utils::basename($row['path']);
			yield $row;
		}
	}

	static public function categories(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND w.type = ' . Page::TYPE_CATEGORY;
		return self::pages($params, $tpl, $line);
	}

	static public function articles(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND w.type = ' . Page::TYPE_PAGE;
		return self::pages($params, $tpl, $line);
	}

	static public function pages(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['select'] = 'w.*';
		$params['tables'] = 'web_pages w';
		$params['where'] .= ' AND status = :status';
		$params[':status'] = Page::STATUS_ONLINE;

		if (array_key_exists('search', $params)) {
			if (trim((string) $params['search']) === '') {
				return;
			}

			$params[':search'] = substr(trim($params['search']), 0, 100);
			unset($params['search']);

			$params['tables'] .= ' INNER JOIN files_search ON files_search.path = w.file_path';
			$params['select'] .= ', rank(matchinfo(files_search), 0, 1.0, 1.0) AS points, snippet(files_search, \'<mark>\', \'</mark>\', \'…\', 2) AS snippet';
			$params['where'] .= ' AND files_search MATCH :search';

			$params['order'] = 'points DESC';
			$params['limit'] = '30';
		}

		if (isset($params['uri'])) {
			$params['where'] .= ' AND w.uri = :uri';
			$params['limit'] = 1;
			$params[':uri'] = $params['uri'];
			unset($params['uri']);
		}

		if (isset($params['path'])) {
			$params['where'] .= ' AND w.path = :path';
			$params['limit'] = 1;
			$params[':path'] = $params['path'];
			unset($params['path']);
		}

		if (array_key_exists('parent', $params)) {
			$params['where'] .= ' AND w.parent = :parent';
			$params[':parent'] = trim((string) $params['parent']);

			unset($params['parent']);
		}

		if (isset($params['future'])) {
			$params['where'] .= sprintf(' AND w.published %s datetime(\'now\', \'localtime\')', $params['future'] ? '>' : '<=');
			unset($params['future']);
		}

		foreach (self::sql($params, $tpl, $line) as $row) {
			if (empty($params['count'])) {
				$data = $row;
				unset($data['points'], $data['snippet']);

				$page = new Page;
				$page->exists(true);
				$page->load($data);

				if (!$page->file()) {
					continue;
				}

				if (isset($row['snippet'])) {
					$row['snippet'] = preg_replace('!</b>(\s*)<b>!', '$1', $row['snippet']);
					if (preg_match('!<b>(.*?)</b>!', $row['snippet'], $match)) {
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
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND image = 1';
		return self::files($params, $tpl, $line);
	}

	static public function documents(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND image = 0';
		return self::files($params, $tpl, $line);
	}

	static public function files(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		if (empty($params['parent'])) {
			throw new Brindille_Exception('La section "files" doit obligatoirement comporter un paramètre "parent"');
		}

		$parent = $params['parent'];

		// Fetch page
		$page = self::cache('page_' . md5($parent), function () use ($parent) {
			$page = Web::get($parent);

			if (!$page) {
				return null;
			}

			// Store attachments in temp table
			$db = DB::getInstance();
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
				$row['url'] = $file->url();
				$row['download_url'] = $file->url(true);
				$row['thumb_url'] = $file->thumb_url();
				$row['small_url'] = $file->thumb_url(File::THUMB_SIZE_SMALL);

				$db->preparedQuery('INSERT OR REPLACE INTO web_pages_attachments VALUES (?, ?, ?, ?, ?, ?, ?);',
					$page->id(), rawurldecode($file->uri()), $file->path, $file->name, $file->modified, $file->isImage(), json_encode($row));
			}

			$db->commit();

			return $page;
		});

		if (!$page) {
			return;
		}

		$params['select'] = 'data';
		$params['tables'] = 'web_pages_attachments';
		$params['where'] .= ' AND page_id = :page';
		$params[':page'] = $page->id();
		unset($params['parent']);

		// Generate a temporary table containing the list of files included in the text
		if (!empty($params['except_in_text'])) {
			// Don't regenerate that table for each section called in the page,
			// we assume the content and list of files will not change between sections
			self::cache('page_files_text_' . $parent, function () use ($page) {
				$db = DB::getInstance();
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

		$module = DB::getInstance()->first('SELECT * FROM modules WHERE name = ?;', $params['name']);

		if (!$module || !$module->enabled) {
			return null;
		}

		$module->config = $module->config ? @json_decode($module->config) : null;
		$module->path = 'modules/' . $module->name;

		yield (array) $module;
	}

	static public function sql(array $params, UserTemplate $tpl, int $line): \Generator
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

			// Replace raw SQL parameters (undocumented feature, this is for #select section)
			foreach ($params as $k => $v) {
				if (substr($k, 0, 1) == '!') {
					$r = '/' . preg_quote($k, '/') . '\b/';
					$sql = preg_replace($r, $v, $sql);
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
			$statement = $db->protectSelect(self::SQL_TABLES, $sql);

			$args = [];

			foreach ($params as $key => $value) {
				if (substr($key, 0, 1) == ':') {
					$args[$key] = $value;
				}
			}

			foreach ($args as $key => $value) {
				$statement->bindValue($key, $value, $db->getArgType($value));
			}

			if (!empty($params['debug'])) {
				self::_debug($statement->getSQL(true));
			}

			if (!empty($params['explain'])) {
				self::_debugExplain($statement->getSQL(true));
			}

			$result = $statement->execute();
		}
		catch (\KD2\DB\DB_Exception $e) {
			throw new Brindille_Exception(sprintf("à la ligne %d erreur SQL :\n%s\n\nRequête exécutée :\n%s", $line, $db->lastErrorMsg(), $sql));
		}

		while ($row = $result->fetchArray(\SQLITE3_ASSOC))
		{
			if (isset($params['assign'])) {
				$tpl->assign($params['assign'], $row, 0);
			}

			yield $row;
		}
	}
}
