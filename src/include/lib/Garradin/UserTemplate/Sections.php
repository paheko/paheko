<?php

namespace Garradin\UserTemplate;

use KD2\Brindille_Exception;
use Garradin\DB;
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
		'transaction_users',
		'balances',
		'sql',
		'restrict',
	];

	static protected $_cache = [];

	static protected function cache(string $id, callable $callback)
	{
		if (!array_key_exists($id, self::$_cache)) {
			self::$_cache[$id] = $callback();
		}

		return self::$_cache[$id];
	}

	static public function load(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$name = Utils::basename(Utils::dirname($tpl->_tpl_path));

		if (!$name) {
			throw new Brindille_Exception('Unique document name could not be found');
		}

		if (!isset($params['where'])) {
			$params['where'] = '1';
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

		$params['select'] = isset($params['select']) ? $params['select'] : 'value AS json';
		$params['tables'] = 'user_forms_' . $name;

		try {
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
		catch (Brindille_Exception $e) {
			// Table does not exists: return nothing
			if (false !== strpos($e->getMessage(), 'no such table: ' . $params['tables'])) {
				return;
			}

			throw $e;
		}
	}

	static public function balances(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$db = DB::getInstance();

		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['tables'] = $table;

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

	static public function users(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$id_field = DynamicFields::getNameFieldsSQL();
		$login_field = DynamicFields::getLoginField();
		$number_field = DynamicFields::getNumberField();

		$params['select'] = sprintf('*, %s AS user_name, %s AS user_login, %s AS user_number',
			$id_field, $login_field, $number_field);
		$params['tables'] = 'users';

		if (isset($params['id'])) {
			$params['where'] = ' AND id = :id';
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
			$params['where'] = ' AND su.id_user = :id_user';
			$params[':id_user'] = (int) $params['user'];
			unset($params['user']);
		}

		if (!empty($params['active'])) {
			$params['where'] = ' AND MAX(su.expiry_date) >= date()';
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
			$params['where'] = ' AND t.id = :id';
			$params[':id'] = (int) $params['id'];
			unset($params['id']);
		}

		$id_field = DynamicFields::getNameFieldsSQL('u');

		$params['select'] = sprintf('t.*, SUM(l.credit) AS credit, SUM(l.debit) AS debit,
			GROUP_CONCAT(DISTINCT a.code) AS accounts_codes,
			GROUP_CONCAT(DISTINCT %s) AS users_names', $id_field);
		$params['tables'] = 'acc_transactions AS t
			INNER JOIN acc_transactions_lines AS l ON l.id_transaction = t.id
			INNER JOIN acc_accounts AS a ON l.id_account = a.id
			LEFT JOIN acc_transactions_users tu ON tu.id_transaction = t.id
			LEFT JOIN users u ON u.id = tu.id_user';
		$params['group'] = 't.id, u.id';

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
			$params['select'] .= ', rank(matchinfo(files_search), 0, 1.0, 1.0) AS points, snippet(files_search, \'<b>\', \'</b>\', \'…\', 2) AS snippet';
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
			$db->exec('CREATE TEMP TABLE IF NOT EXISTS web_pages_attachments (page_id, uri, path, name, modified, image);');
			$page_file_name = Utils::basename($page->file_path);

			foreach ($page->listAttachments() as $file) {
				if ($file->name == $page_file_name || $file->type != File::TYPE_FILE) {
					continue;
				}

				$db->preparedQuery('INSERT OR REPLACE INTO web_pages_attachments VALUES (?, ?, ?, ?, ?, ?);',
					$page->id(), $file->uri(), $file->path, $file->name, $file->modified, $file->image);
			}

			$db->commit();

			return $page;
		});

		if (!$page) {
			return;
		}

		$params['select'] = 'path';
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
			$file = Files::get($row['path']);

			if (null === $file) {
				continue;
			}

			$row = $file->asArray();
			$row['url'] = $file->url();
			$row['download_url'] = $file->url(true);
			$row['thumb_url'] = $file->thumb_url();
			$row['small_url'] = $file->thumb_url(File::THUMB_SIZE_SMALL);
			yield $row;
		}
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

		if (!isset($params['tables'])) {
			throw new Brindille_Exception('Missing parameter "tables"');
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

		$db = DB::getInstance();

		try {
			$statement = $db->protectSelect(null, $sql);

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
				echo sprintf('<pre style="padding: 5px; background: yellow; white-space: normal;">%s</pre>', htmlspecialchars($statement->getSQL(true)));
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
