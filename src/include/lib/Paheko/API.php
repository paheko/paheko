<?php

namespace Paheko;

use Paheko\Backup;
use Paheko\Users\Session;
use Paheko\Search;
use Paheko\Services\Subscriptions;
use Paheko\Files\Files;

use KD2\ErrorManager;
use KD2\DB\DB_Exception;

class API
{
	use API\Accounting;
	use API\User;
	use API\Web;

	protected string $path;
	protected array $params;
	protected bool $is_http_client = false;
	protected string $method;
	protected int $access;
	protected $file_pointer = null;
	protected ?string $allowed_files_root = null;

	const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'DELETE'];

	const EXPORT_FORMATS = ['json', 'xlsx', 'ods', 'csv'];

	const SUCCESS = ['success' => true];

	public function __construct(string $method, string $path, array $params, bool $is_http_client = false)
	{
		if (!in_array($method, self::ALLOWED_METHODS)) {
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

	protected function requireMethod(string $method)
	{
		if ($this->method !== $method) {
			throw new APIException('Wrong request method', 405);
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

	protected function hasParamTrue(string $param): bool
	{
		return array_key_exists($param, $this->params) && in_array($this->params[$param], [1, true, 'true', 'TRUE', '1'], true);
	}

	protected function toArray($in, bool $recursive = true): array
	{
		if (!is_array($in) && is_iterable($in)) {
			$in = iterator_to_array($in);
		}

		$in = (array)$in;

		foreach ($in as $key => &$value) {
			if ($recursive && (is_array($value) || is_iterable($value))) {
				$value = $this->toArray($value);
			}
			elseif ($value instanceof \DateTime) {
				if ((int)$value->format('His')) {
					$value = $value->format('Y-m-d H:i:s');
				}
				else {
					$value = $value->format('Y-m-d');
				}
			}
		}

		unset($value);
		return $in;
	}

	public function exportJSON($in, $level = 2): void
	{
		$is_list = null;
		$in = $this->toArray($in, false);

		foreach ($in as $key => $value) {
			if (null === $is_list) {
				if ($key === 0) {
					echo "[\n";
					$is_list = true;
				}
				else {
					echo "{\n";
					$is_list = false;
				}
			}
			else {
				echo ",\n";
			}

			echo str_repeat(" ", $level);

			if (!$is_list) {
				echo json_encode((string)$key) . ': ';
			}

			if (is_array($value) || is_object($value)) {
				$this->exportJSON($value, $level+2);
			}
			else {
				echo json_encode($value);
			}
		}

		$space = str_repeat(' ', max($level-2, 0));
		if ($is_list === true) {
			echo "\n$space]";
		}
		elseif ($is_list === false) {
			echo "\n$space}";
		}
		else {
			echo "[]\n";
		}

		flush();
	}

	public function export($in): ?array
	{
		if (null === $in) {
			return null;
		}

		if (!$this->is_http_client) {
			$in = $this->toArray($in);
			return json_encode($in);
		}

		header("Content-Type: application/json; charset=utf-8", true);
		echo $this->exportJSON($in);
		return null;
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

	protected function sql(string $format)
	{
		if ($this->method !== 'POST' && $this->method !== 'GET') {
			throw new APIException('Wrong request method', 405);
		}

		$body = $this->params['sql'] ?? self::getRequestInput();
		$format = $format ?: ($this->params['format'] ?? 'json');

		if (!in_array($format, self::EXPORT_FORMATS, true)) {
			throw new APIException('Invalid format. Supported formats: ' . implode(', ', self::EXPORT_FORMATS));
		}

		if ($body === '') {
			throw new APIException('Missing SQL statement', 400);
		}

		try {
			$s = Search::fromSQL($body);
			$result = $s->iterateResults();
			$header = $s->getHeader();

			if ($format !== 'json') {
				$return_header = !isset($this->params['header']) || $this->params['header'] !== 'false';
				$s->export($format, 'sql', $return_header);
				return null;
			}
			elseif (!$this->is_http_client) {
				return $this->export(['count' => $s->countResults(), 'results' => $result]);
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
				$csv->setColumns(Subscriptions::listImportColumns());
				$csv->setMandatoryColumns(Subscriptions::listMandatoryImportColumns());

				$csv->loadFile($path);
				$csv->setTranslationTableAuto();

				if (!$csv->loaded() || !$csv->ready()) {
					throw new APIException('Missing columns or error during columns matching of import table: ' . json_encode(Subscriptions::listMandatoryImportColumns()), 400);
				}

				Subscriptions::import($csv);
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

		if (substr($uri, 0, 3) === 'sql') {
			return $this->sql(trim(substr($uri, 3), '.'));
		}

		$fn = strtok($uri, '/');
		$uri = strtok('');

		switch ($fn) {
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

			return $api->export($return);
		}
		catch (APIException $e) {
			http_response_code($e->getCode());
			header("Content-Type: application/json; charset=utf-8", true);
			echo json_encode(['error' => $e->getMessage()]);
		}
	}
}