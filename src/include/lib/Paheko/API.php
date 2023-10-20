<?php

namespace Paheko;

use Paheko\Backup;
use Paheko\Users\Session;
use Paheko\Web\Web;
use Paheko\Accounting\Accounts;
use Paheko\Accounting\Charts;
use Paheko\Accounting\Reports;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Search;
use Paheko\Users\DynamicFields;
use Paheko\Users\Users;

use KD2\ErrorManager;

class API
{
	protected $body;
	protected $params;
	protected $method;
	protected int $access;

	protected function requireAccess(int $level)
	{
		if ($this->access < $level) {
			throw new APIException('You do not have enough rights to make this request', 403);
		}
	}

	protected function body(): string
	{
		if (null == $this->body) {
			$this->body = trim(file_get_contents('php://input'));
		}

		return $this->body;
	}

	protected function hasParam(string $param): bool
	{
		return array_key_exists($param, $_GET);
	}

	protected function download()
	{
		if ($this->method != 'GET') {
			throw new APIException('Wrong request method', 400);
		}

		Backup::dump();
		return null;
	}

	protected function sql()
	{
		if ($this->method != 'POST') {
			throw new APIException('Wrong request method', 400);
		}

		$body = $this->body();

		if ($body === '') {
			throw new APIException('Missing SQL statement', 400);
		}

		try {
			$s = Search::fromSQL($body);
			$result = $s->iterateResults();
			$header = $s->getHeader();

			if (isset($_GET['format']) && in_array($_GET['format'], ['xlsx', 'ods', 'csv'])) {
				$s->export($_GET['format']);
				return null;
			}
			else {
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
		catch (\Exception $e) {
			http_response_code(400);
			return ['error' => 'Error in SQL statement', 'sql_error' => $e->getMessage()];
		}
	}

	protected function user(string $uri): ?array
	{
		$fn = strtok($uri, '/');
		$fn2 = strtok('/');

		// CSV import
		if ($fn == 'import') {
			$fp = null;

			if ($this->method === 'PUT') {
				$params = $_GET;
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
				$stdin = fopen('php://input', 'rb');
				$fp = fopen($path, 'wb');
				stream_copy_to_stream($stdin, $fp);
				fclose($fp);
				fclose($stdin);
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
		$p2 = strtok(false);

		if ($fn == 'transaction') {
			if (!$p1) {
				if ($this->method != 'POST') {
					throw new APIException('Wrong request method', 400);
				}

				$this->requireAccess(Session::ACCESS_WRITE);
				$transaction = new Transaction;
				$transaction->importFromAPI();
				$transaction->save();
				return $transaction->asJournalArray();
			}
			// Return or edit linked users
			elseif (ctype_digit($p1) && $p2 == 'users') {
				$transaction = Transactions::get((int)$p1);

				if (!$transaction) {
					throw new APIException(sprintf('Transaction #%d not found', $p1), 404);
				}

				if ($this->method === 'POST') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->updateLinkedUsers((array)($_POST['users'] ?? null));
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
			elseif (ctype_digit($p1) && !$p2) {
				$transaction = Transactions::get((int)$p1);

				if (!$transaction) {
					throw new APIException(sprintf('Transaction #%d not found', $p1), 404);
				}

				if ($this->method == 'GET') {
					return $transaction->asJournalArray();
				}
				elseif ($this->method == 'POST') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->importFromNewForm();
					$transaction->save();
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
		elseif ($fn == 'years') {
			if ($this->method != 'GET') {
				throw new APIException('Wrong request method', 400);
			}

			if (($p1 === 'current' || ctype_digit($p1)) && ($p2 === 'journal' || $p2 === 'account/journal')) {
				if ($p1 === 'current') {
					$id_year = Years::getCurrentOpenYearId();

					if (!$id_year) {
						throw new APIException('There are no currently open years', 404);
					}
				}
				else {
					$id_year = (int)$match[1];
				}

				if ($p2 == 'journal') {
					try {
						return iterator_to_array(Reports::getJournal(['year' => $id_year]));
					}
					catch (\LogicException $e) {
						throw new APIException('Missing parameter for journal: ' . $e->getMessage(), 400, $e);
					}
				}
				else {
					$year = Years::get($id_year);

					if (!$year) {
						throw new APIException('Invalid year.', 400, $e);
					}

					$a = $year->chart()->accounts();

					if (!empty($_GET['code'])) {
						$account = $a->getWithCode($_GET['code']);
					}
					else {
						$account = $a->get((int)$_GET['code'] ?? null);
					}

					if (!$account) {
						throw new APIException('Unknown account id or code.', 400, $e);
					}

					$list = $account->listJournal($year->id, false);
					$list->setTitle(sprintf('Journal - %s - %s', $account->code, $account->label));
					$list->loadFromQueryString();
					$list->setPageSize(null);
					$list->orderBy('date', false);
					return iterator_to_array($list->iterate());
				}
			}
			elseif (!$p1 && !$p2) {
				return Years::list();
			}
			else {
				throw new APIException('Unknown years action', 404);
			}
		}
		elseif ($fn == 'charts') {
			if ($this->method != 'GET') {
				throw new APIException('Wrong request method', 400);
			}

			if (ctype_digit($p1) && $p2 === 'accounts') {
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
		else {
			throw new APIException('Unknown accounting action', 404);
		}
	}

	public function errors(string $uri)
	{
		$fn = strtok($uri, '/');

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

			$body = $this->body();
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
		if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
			throw new APIException('No username or password supplied', 401);
		}

		if (API_USER && API_PASSWORD && $_SERVER['PHP_AUTH_USER'] === API_USER && $_SERVER['PHP_AUTH_PW'] === API_PASSWORD) {
			$this->access = Session::ACCESS_ADMIN;
		}
		elseif ($c = API_Credentials::login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
			$this->access = $c->access_level;
		}
		else {
			throw new APIException('Invalid username or password', 403);
		}
	}

	public function dispatch(string $fn, string $uri)
	{
		$this->checkAuth();

		switch ($fn) {
			case 'sql':
				return $this->sql();
			case 'download':
				return $this->download();
			case 'web':
				return $this->web($uri);
			case 'user':
				return $this->user($uri);
			case 'errors':
				return $this->errors($uri);
			case 'accounting':
				return $this->accounting($uri);
			default:
				throw new APIException('Unknown path', 404);
		}
	}

	static public function dispatchURI(string $uri)
	{
		$fn = strtok($uri, '/');

		$api = new self;

		$api->method = $_SERVER['REQUEST_METHOD'] ?? null;
		$type = $_SERVER['CONTENT_TYPE'] ?? null;
		$type ??= $_SERVER['HTTP_CONTENT_TYPE'] ?? null;

		if ($api->method === 'POST' && false !== strpos($type, '/json')) {
			$_POST = (array) json_decode($api->body(), true);
		}

		http_response_code(200);

		try {
			$return = $api->dispatch($fn, strtok(''));

			if (null !== $return) {
				header("Content-Type: application/json; charset=utf-8", true);
				echo json_encode($return, JSON_PRETTY_PRINT);
			}
		}
		catch (\Exception $e) {
			if ($e instanceof APIException) {
				http_response_code($e->getCode());
				header("Content-Type: application/json; charset=utf-8", true);
				echo json_encode(['error' => $e->getMessage()]);
			}
			elseif ($e instanceof UserException || $e instanceof ValidationException) {
				http_response_code(400);
				header("Content-Type: application/json; charset=utf-8", true);
				echo json_encode(['error' => $e->getMessage()]);
			}
			else {
				throw $e;
			}
		}
	}
}