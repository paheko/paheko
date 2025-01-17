<?php

namespace Paheko;

use Paheko\Backup;
use Paheko\Users\Session;
use Paheko\Web\Web;
use Paheko\Accounting\Accounts;
use Paheko\Accounting\Charts;
use Paheko\Accounting\Export;
use Paheko\Accounting\Reports;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Search;
use Paheko\Services\Services_User;
use Paheko\Users\Categories;
use Paheko\Users\DynamicFields;
use Paheko\Users\Users;
use Paheko\Files\Files;

use KD2\ErrorManager;
use KD2\DB\DB_Exception;

class API
{
	protected string $path;
	protected array $params;
	protected bool $is_http_client = false;
	protected string $method;
	protected int $access;
	protected $file_pointer = null;
	protected ?string $allowed_files_root = null;

	protected array $allowed_methods = ['GET', 'POST', 'PUT', 'DELETE'];

	public function __construct(string $method, string $path, array $params, bool $is_http_client)
	{
		if (!in_array($method, $this->allowed_methods)) {
			throw new APIException('Invalid request method: ' . $method, 405);
		}

		$this->path = trim($path, '/');
		$this->method = $method;
		$this->params = $params;
		$this->is_http_client = $is_http_client;
	}

	public function requireHttpClient(): void
	{
		if ($this->is_http_client) {
			return;
		}

		throw new APIException('This request is not yet supported in Brindille', 501);
	}

	public function __destruct()
	{
		if (null !== $this->file_pointer) {
			$this->closeFilePointer();
		}
	}

	public function setAllowedFilesRoot(?string $root): void
	{
		$this->allowed_files_root = rtrim($root, '/') . '/';
	}

	public function isPathAllowed(string $path): bool
	{
		if (!$this->allowed_files_root) {
			return false;
		}

		return 0 === strpos($path, $this->allowed_files_root);
	}

	public function setAccessLevelByName(string $level): void
	{
		if ($level === 'read') {
			$this->access = Session::ACCESS_READ;
		}
		elseif ($level === 'write') {
			$this->access = Session::ACCESS_WRITE;
		}
		elseif ($level === 'admin') {
			$this->access = Session::ACCESS_ADMIN;
		}
		else {
			throw new \InvalidArgumentException('Invalid access level: ' . $level);
		}
	}

	public function setFilePointer($pointer): void
	{
		if (!is_resource($pointer)) {
			throw new \InvalidArgumentException('Invalid argument: not a file resource');
		}

		$this->file_pointer = $pointer;
	}

	public function closeFilePointer(): void
	{
		@fclose($this->file_pointer);
		$this->file_pointer = null;
	}

	protected function requireAccess(int $level)
	{
		if ($this->access < $level) {
			throw new APIException('You do not have enough rights to make this request', 403);
		}
	}

	protected function isSystemUser(): bool
	{
		$login = $_SERVER['PHP_AUTH_USER'] ?? null;
		$password = $_SERVER['PHP_AUTH_PW'] ?? null;

		return API_USER
			&& API_PASSWORD
			&& $login === API_USER
			&& $password === API_PASSWORD;
	}

	protected function hasParam(string $param): bool
	{
		return array_key_exists($param, $this->params);
	}

	protected function download(string $uri)
	{
		if ($this->method != 'GET') {
			throw new APIException('Wrong request method', 400);
		}

		if ($uri === 'files') {
			Files::zipAll();
		}
		elseif ($uri === '') {
			Backup::dump();
		}
		else {
			throw new APIException('Unknown path: ' . $uri, 404);
		}

		return null;
	}

	protected function sql()
	{
		if ($this->method !== 'POST' && $this->method !== 'GET') {
			throw new APIException('Wrong request method', 400);
		}

		$body = $this->params['sql'] ?? self::getRequestInput();

		if ($body === '') {
			throw new APIException('Missing SQL statement', 400);
		}

		try {
			$s = Search::fromSQL($body);
			$result = $s->iterateResults();
			$header = $s->getHeader();

			if (isset($this->params['format']) && in_array($this->params['format'], ['xlsx', 'ods', 'csv'])) {
				$s->export($this->params['format']);
				return null;
			}
			elseif (!$this->is_http_client) {
				return ['count' => $s->countResults(), 'results' => iterator_to_array($result)];
			}
			else {
				// Stream results to client, in case request is slow
				header("Content-Type: application/json; charset=utf-8", true);
				printf("{\n    \"count\": %d,\n    \"results\":\n    [\n", $s->countResults());

				foreach ($result as $i => $row) {
					$line = [];

					foreach ($row as $n => $v) {
						$name = $header[$n];

						// Avoid name collision
						while (isset($line[$name])) {
							$name .= '_' . $n;
						}

						$line[$name] = $v;
					}

					if ($i > 0) {
						echo ",\n";
					}

					$json = json_encode($line, JSON_PRETTY_PRINT);
					$json = "        " . str_replace("\n", "\n        ", $json);
					echo $json;
				}

				echo "\n    ]\n}";

				return null;
			}
		}
		catch (DB_Exception $e) {
			throw new APIException('Error in SQL statement: ' . $e->getMessage(), 400);
		}
	}

	protected function user(string $uri): ?array
	{
		$fn = (string) strtok($uri, '/');
		$fn2 = strtok('/');
		strtok('');

		if ($fn === 'categories') {
			return Categories::listWithStats();
		}
		elseif ($fn === 'category') {
			$id = (int) strtok($fn2, '.');
			$format = strtok('');

			try {
				Users::exportCategory($format ?: 'json', $id, true);
			}
			catch (\InvalidArgumentException $e) {
				throw new APIException($e->getMessage(), 400, $e);
			}

			return null;
		}
		elseif ($fn === 'new') {
			$this->requireAccess(Session::ACCESS_WRITE);

			$user = Users::create();
			$user->importForm($this->params);
			$user->setNumberIfEmpty();

			if (empty($this->params['force_duplicate']) && $user->checkDuplicate()) {
				throw new APIException('This user seems to be a duplicate of an existing one', 409);
			}

			if (!empty($this->params['id_category'])) {
				if ($this->isSystemUser()) {
					$user->set('id_category', (int)$this->params['id_category']);
				}
				elseif (!$user->setCategorySafeNoConfig((int)$this->params['id_category'])) {
					throw new APIException('You are not allowed to create a user in this category', 403);
				}
			}

			if (isset($this->params['password'])) {
				$user->setNewPassword(['password' => $this->params['password'], 'password_confirmed' => $this->params['password']], false);
			}

			$user->save();

			return $user->exportAPI();
		}
		elseif (ctype_digit($fn)) {
			$user = Users::get((int)$fn);

			if (!$user) {
				throw new APIException('The requested user ID does not exist', 404);
			}

			if ($this->method === 'POST') {
				$this->requireAccess(Session::ACCESS_WRITE);

				try {
					$user->validateCanBeModifiedBy(null);
				}
				catch (UserException $e) {
					throw new APIException($e->getMessage(), 403, $e);
				}

				$user->importForm($this->params);
				$user->save();
			}
			elseif ($this->method === 'DELETE') {
				$this->requireAccess(Session::ACCESS_ADMIN);

				try {
					$user->validateCanBeModifiedBy(null);
				}
				catch (UserException $e) {
					throw new APIException($e->getMessage(), 403, $e);
				}

				$user->delete();
				return ['success' => true];
			}

			return $user->exportAPI();
		}
		elseif ($fn === 'import') {
			$this->requireHttpClient();

			if ($this->method === 'PUT') {
				$params = $this->params;
			}
			elseif ($this->method === 'POST') {
				$params = $_POST;
			}
			else {
				throw new APIException('Wrong request method', 400);
			}

			$mode = $params['mode'] ?? 'auto';

			if (!in_array($mode, ['auto', 'create', 'update'])) {
				throw new APIException('Unknown mode. Only "auto", "create" and "update" are accepted.', 400);
			}

			$this->requireAccess(Session::ACCESS_ADMIN);

			$path = tempnam(CACHE_ROOT, 'tmp-import-api');

			if ($this->method === 'POST') {
				if (empty($_FILES['file']['tmp_name']) || !empty($_FILES['file']['error'])) {
					throw new APIException('Empty file or no file was sent.', 400);
				}

				$path = $_FILES['file']['tmp_name'] ?? null;
			}
			else {
				$fp = fopen($path, 'wb');
				stream_copy_to_stream($this->file_pointer, $fp);
				fclose($fp);
				$this->closeFilePointer();
			}

			try {
				if (!filesize($path)) {
					throw new APIException('Empty CSV file', 400);
				}

				$csv = new CSV_Custom;
				$df = DynamicFields::getInstance();
				$csv->setColumns($df->listImportAssocNames());
				$required_fields = $df->listImportRequiredAssocNames($mode === 'update' ? true : false);
				$csv->setMandatoryColumns(array_keys($required_fields));
				$csv->loadFile($path);
				$csv->skip((int)($params['skip_lines'] ?? 1));

				if (!empty($params['column']) && is_array($params['column'])) {
					$csv->setIndexedTable($params['column']);
				}
				else {
					$csv->setTranslationTableAuto();
				}

				if (!$csv->loaded() || !$csv->ready()) {
					throw new APIException('Missing columns or error during columns matching of import table', 400);
				}

				if ($fn2 === 'preview') {
					$report = Users::importReport($csv, $mode);

					$report['unchanged'] = array_map(
						fn($user) => ['id' => $user->id(), 'name' => $user->name()],
						$report['unchanged']
					);

					$report['created'] = array_map(
						fn($user) => $user->asDetailsArray(),
						$report['created']
					);

					$report['modified'] = array_map(
						function ($user) {
							$out = ['id' => $user->id(), 'name' => $user->name(), 'changed' => []];

							foreach ($user->getModifiedProperties() as $key => $value) {
								$out['changed'][$key] = ['old' => $value, 'new' => $user->$key];
							}

							return $out;
						},
						$report['modified']
					);


					return $report;
				}
				else {
					Users::import($csv, $mode);
					return null;
				}
			}
			finally {
				Utils::safe_unlink($path);
			}
		}
		else {
			throw new APIException('Unknown user action', 404);
		}
	}

	protected function web(string $uri): ?array
	{
		if ($this->method != 'GET') {
			throw new APIException('Wrong request method', 400);
		}

		$fn = strtok($uri, '/');
		$param = strtok('');

		switch ($fn) {
			case 'list':
				return [
					'categories' => array_map(fn($p) => $p->asArray(true), Web::listCategories($param)),
					'pages' => array_map(fn($p) => $p->asArray(true), Web::listPages($param)),
				];
			case 'attachment':
				$attachment = Files::getFromURI($param);

				if (!$attachment) {
					throw new APIException('Page not found', 404);
				}

				$attachment->serve();
				return null;
			case 'html':
			case 'page':
				$page = Web::getByURI($param);

				if (!$page) {
					throw new APIException('Page not found', 404);
				}

				if ($fn == 'page') {
					$out = $page->asArray(true);

					if ($this->hasParam('html')) {
						$out['html'] = $page->render();
					}

					return $out;
				}

				// HTML render
				echo $page->render();
				return null;
			default:
				throw new APIException('Unknown web action', 404);
		}
	}

	protected function accounting(string $uri): ?array
	{
		$fn = strtok($uri, '/');
		$p1 = strtok('/');
		$p2 = strtok('');

		if ($fn == 'transaction') {
			if (!$p1) {
				if ($this->method != 'POST') {
					throw new APIException('Wrong request method', 400);
				}

				$this->requireAccess(Session::ACCESS_WRITE);
				$transaction = new Transaction;
				$transaction->importFromAPI($this->params);
				$transaction->save();

				if (!empty($this->params['linked_users'])) {
					$transaction->updateLinkedUsers((array)$this->params['linked_users']);
				}

				if (!empty($this->params['linked_transactions'])) {
					$transaction->updateLinkedTransactions((array)$this->params['linked_transactions']);
				}

				if (!empty($this->params['linked_subscriptions'])) {
					$transaction->updateSubscriptionLinks((array)$this->params['linked_subscriptions']);
				}

				if ($this->hasParam('move_attachments_from')
					&& $this->isPathAllowed($this->params['move_attachments_from'])) {
					$file = Files::get($this->params['move_attachments_from']);

					if ($file && $file->isDir()) {
						$file->rename($transaction->getAttachementsDirectory());
					}
				}

				return $transaction->asJournalArray();
			}
			// Return or edit linked transactions
			elseif ($p1 && ctype_digit($p1) && $p2 == 'transactions') {
				$transaction = Transactions::get((int)$p1);

				if (!$transaction) {
					throw new APIException(sprintf('Transaction #%d not found', $p1), 404);
				}

				if ($this->method === 'POST') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->updateLinkedTransactions((array)($this->params['transactions'] ?? null));
					return ['success' => true];
				}
				elseif ($this->method === 'DELETE') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->deleteLinkedTransactions();
					return ['success' => true];
				}
				elseif ($this->method === 'GET') {
					return array_values($transaction->listLinkedTransactionsAssoc());
				}
				else {
					throw new APIException('Wrong request method', 400);
				}
			}
			// Return or edit linked users
			elseif ($p1 && ctype_digit($p1) && $p2 == 'users') {
				$transaction = Transactions::get((int)$p1);

				if (!$transaction) {
					throw new APIException(sprintf('Transaction #%d not found', $p1), 404);
				}

				if ($this->method === 'POST') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->updateLinkedUsers((array)($this->params['users'] ?? null));
					return ['success' => true];
				}
				elseif ($this->method === 'DELETE') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->updateLinkedUsers([]);
					return ['success' => true];
				}
				elseif ($this->method === 'GET') {
					return $transaction->listLinkedUsers();
				}
				else {
					throw new APIException('Wrong request method', 400);
				}
			}
			// Return or edit linked subscriptions
			elseif ($p1 && ctype_digit($p1) && $p2 == 'subscriptions') {
				$transaction = Transactions::get((int)$p1);

				if (!$transaction) {
					throw new APIException(sprintf('Transaction #%d not found', $p1), 404);
				}

				if ($this->method === 'POST') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->updateSubscriptionLinks((array)($this->params['subscriptions'] ?? null));
					return ['success' => true];
				}
				elseif ($this->method === 'DELETE') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->deleteAllSubscriptionLinks([]);
					return ['success' => true];
				}
				elseif ($this->method === 'GET') {
					return $transaction->listSubscriptionLinks();
				}
				else {
					throw new APIException('Wrong request method', 400);
				}
			}
			elseif ($p1 && ctype_digit($p1) && !$p2) {
				$transaction = Transactions::get((int)$p1);

				if (!$transaction) {
					throw new APIException(sprintf('Transaction #%d not found', $p1), 404);
				}

				if ($this->method == 'GET') {
					return $transaction->asJournalArray();
				}
				elseif ($this->method == 'POST') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->importFromAPI($this->params);
					$transaction->save();

					if (!empty($this->params['linked_users'])) {
						$transaction->updateLinkedUsers((array)$this->params['linked_users']);
					}

					if (!empty($this->params['linked_transactions'])) {
						$transaction->updateLinkedTransactions((array)$this->params['linked_transactions']);
					}

					if (!empty($this->params['linked_subscriptions'])) {
						$transaction->updateSubscriptionLinks((array)$this->params['linked_subscriptions']);
					}

					return $transaction->asJournalArray();
				}
				else {
					throw new APIException('Wrong request method', 400);
				}
			}
			else {
				throw new APIException('Unknown transactions route', 404);
			}
		}
		elseif ($fn == 'charts') {
			if ($this->method != 'GET') {
				throw new APIException('Wrong request method', 400);
			}

			if ($p1 && ctype_digit($p1) && $p2 === 'accounts') {
				$a = new Accounts((int)$p1);
				return array_map(fn($c) => $c->asArray(), $a->listAll());
			}
			elseif (!$p1 && !$p2) {
				return array_map(fn($c) => $c->asArray(), Charts::list());
			}
			else {
				throw new APIException('Unknown charts action', 404);
			}
		}
		elseif ($fn == 'years') {
			if ($this->method != 'GET') {
				throw new APIException('Wrong request method', 400);
			}

			if (!$p1 && !$p2) {
				return iterator_to_array(Years::listWithStats());
			}

			$id_year = null;

			if ($p1 === 'current') {
				$id_year = Years::getCurrentOpenYearId();
			}
			elseif ($p1 && ctype_digit($p1)) {
				$id_year = (int)$p1;
			}

			if (!$id_year) {
				throw new APIException('Missing year in request, or no open years exist', 400);
			}

			$year = Years::get($id_year);

			if (!$year) {
				throw new APIException('Invalid year.', 400);
			}

			if ($p2 === 'journal') {
				try {
					return iterator_to_array(Reports::getJournal(['year' => $id_year]));
				}
				catch (\LogicException $e) {
					throw new APIException('Missing parameter for journal: ' . $e->getMessage(), 400, $e);
				}
			}
			elseif (0 === strpos($p2, 'export/')) {
				strtok($p2, '/');
				$type = strtok('.');
				$format = strtok('');
				Export::export($year, $format, $type);
				return null;
			}
			elseif ($p2 === 'account/journal') {
				$a = $year->chart()->accounts();

				if (!empty($this->params['code'])) {
					$account = $a->getWithCode($this->params['code']);
				}
				else {
					$account = $a->get(intval($this->params['id'] ?? null));
				}

				if (!$account) {
					throw new APIException('Unknown account id or code.', 400);
				}

				$list = $account->listJournal($year->id, false);
				$list->setTitle(sprintf('Journal - %s - %s', $account->code, $account->label));
				$list->loadFromQueryString();
				$list->setPageSize(null);
				$list->orderBy('date', false);
				return iterator_to_array($list->iterate());
			}
			else {
				throw new APIException('Unknown years action', 404);
			}
		}
		else {
			throw new APIException('Unknown accounting action', 404);
		}
	}

	protected function services(string $uri): ?array
	{
		$fn = strtok($uri, '/');
		$fn2 = strtok('/');
		strtok('');

		// CSV import
		if ($fn === 'subscriptions' && $fn2 === 'import') {
			$this->requireHttpClient();

			if ($this->method === 'PUT') {
				$params = $this->params;
			}
			elseif ($this->method === 'POST') {
				$params = $_POST;
			}
			else {
				throw new APIException('Wrong request method', 400);
			}

			$this->requireAccess(Session::ACCESS_ADMIN);

			$path = tempnam(CACHE_ROOT, 'tmp-import-api');

			if ($this->method === 'POST') {
				if (empty($_FILES['file']['tmp_name']) || !empty($_FILES['file']['error'])) {
					throw new APIException('Empty file or no file was sent.', 400);
				}

				$path = $_FILES['file']['tmp_name'] ?? null;
			}
			else {
				$fp = fopen($path, 'wb');
				stream_copy_to_stream($this->file_pointer, $fp);
				fclose($fp);
				$this->closeFilePointer();
			}

			if (!$path) {
				throw new APIException('Empty CSV file', 400);
			}

			try {
				if (!filesize($path)) {
					throw new APIException('Invalid upload', 400);
				}

				$csv = new CSV_Custom;
				$csv->setColumns(Services_User::listImportColumns());
				$csv->setMandatoryColumns(Services_User::listMandatoryImportColumns());

				$csv->loadFile($path);
				$csv->setTranslationTableAuto();

				if (!$csv->loaded() || !$csv->ready()) {
					throw new APIException('Missing columns or error during columns matching of import table: ' . json_encode(Services_User::listMandatoryImportColumns()), 400);
				}

				Services_User::import($csv);
				return null;
			}
			finally {
				Utils::safe_unlink($path);
			}
		}
		else {
			throw new APIException('Unknown user action', 404);
		}
	}

	public function errors(string $uri)
	{
		if (!ini_get('error_log')) {
			throw new APIException('The error log is disabled', 404);
		}

		if (!ENABLE_TECH_DETAILS) {
			throw new APIException('Access to error log is disabled.', 403);
		}

		if ($uri == 'report') {
			if ($this->method != 'POST') {
				throw new APIException('Wrong request method', 400);
			}

			$this->requireAccess(Session::ACCESS_ADMIN);

			$body = self::getRequestInput();
			$report = json_decode($body);

			if (!isset($report->context->id)) {
				throw new APIException('Invalid JSON body', 400);
			}

			$log = sprintf('=========== Error ref. %s ===========', $report->context->id)
				. PHP_EOL . PHP_EOL . "Report from API" . PHP_EOL . PHP_EOL
				. '<errorReport>' . PHP_EOL . json_encode($report, \JSON_PRETTY_PRINT)
				. PHP_EOL . '</errorReport>' . PHP_EOL;

			error_log($log);

			return null;
		}
		elseif ($uri == 'log') {
			if ($this->method != 'GET') {
				throw new APIException('Wrong request method', 400);
			}

			return ErrorManager::getReportsFromLog(null, null);
		}
		else {
			throw new APIException('Unknown errors action', 404);
		}
	}

	public function checkAuth(): void
	{
		if ($this->isSystemUser()) {
			$this->access = Session::ACCESS_ADMIN;
			return;
		}

		$login = $_SERVER['PHP_AUTH_USER'] ?? null;
		$password = $_SERVER['PHP_AUTH_PW'] ?? null;

		if (!isset($login, $password)) {
			throw new APIException('No username or password supplied', 401);
		}

		$access = API_Credentials::auth($login, $password);

		if (null === $access) {
			throw new APIException('Invalid username or password', 403);
		}

		$this->access = $access;
	}

	public function route()
	{
		$uri = $this->path;
		$fn = strtok($uri, '/');
		$uri = strtok('');

		switch ($fn) {
			case 'sql':
				return $this->sql();
			case 'download':
				return $this->download($uri);
			case 'web':
				return $this->web($uri);
			case 'user':
				return $this->user($uri);
			case 'errors':
				return $this->errors($uri);
			case 'accounting':
				return $this->accounting($uri);
			case 'services':
				return $this->services($uri);
			default:
				throw new APIException('Unknown path', 404);
		}
	}

	static public function getRequestInput(): string
	{
		static $input = null;
		$input ??= trim(file_get_contents('php://input'));
		return $input;
	}

	static public function routeHttpRequest(string $uri)
	{
		$type = $_SERVER['CONTENT_TYPE'] ?? null;
		$type ??= $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
		$method = $_SERVER['REQUEST_METHOD'] ?? null;

		if ($method === 'POST') {
			if (false !== strpos($type, '/json')) {
				$params = (array) json_decode(self::getRequestInput(), true);
			}
			else {
				$params = array_merge($_GET, $_POST);
			}
		}
		else {
			$params = $_GET;
		}

		http_response_code(200);

		try {
			$api = new self($method, $uri, $params, true);

			if ($method === 'PUT') {
				$api->setFilePointer(fopen('php://input', 'rb'));
			}

			$api->checkAuth();

			try {
				$return = $api->route();
			}
			catch (UserException|ValidationException $e) {
				throw new APIException($e->getMessage(), 400, $e);
			}

			if (null !== $return) {
				header("Content-Type: application/json; charset=utf-8", true);
				echo json_encode($return, JSON_PRETTY_PRINT);
			}
		}
		catch (APIException $e) {
			http_response_code($e->getCode());
			header("Content-Type: application/json; charset=utf-8", true);
			echo json_encode(['error' => $e->getMessage()]);
		}
	}
}