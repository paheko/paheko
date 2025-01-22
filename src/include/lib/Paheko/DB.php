<?php

namespace Paheko;

use KD2\DB\SQLite3;
use KD2\DB\DB_Exception;
use KD2\ErrorManager;

use Paheko\Users\DynamicFields;
use Paheko\Entities\Email\Email;

class DB extends SQLite3
{
	/**
	 * Application ID pour SQLite
	 * @link https://www.sqlite.org/pragma.html#pragma_application_id
	 */
	const APPID = 0x5da2d811;

	static protected $_instance = null;

	protected $_version = -1;

	static protected $unicode_patterns_cache = [];

	protected $_log_last = null;
	protected $_log_start = null;
	protected $_log_store = [];

	protected $_schema_update = 0;

	protected bool $_install_check = true;

	static public function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new DB('sqlite', ['file' => DB_FILE]);
		}

		return self::$_instance;
	}

	static public function deleteInstance()
	{
		self::$_instance = null;
	}

	static public function isInstalled(): bool
	{
		return file_exists(DB_FILE) && filesize(DB_FILE);
	}

	static public function isUpgradeRequired(): bool
	{
		$v = self::getInstance()->version();
		return version_compare($v, paheko_version(), '<');
	}

	static public function isVersionTooNew(): bool
	{
		$v = self::getInstance()->version();
		return version_compare($v, paheko_version(), '>');
	}

	private function __clone()
	{
		// Désactiver le clonage, car on ne veut qu'une seule instance
	}

	public function __construct(string $driver, array $params)
	{
		if (self::$_instance !== null) {
			throw new \LogicException('Cannot start instance');
		}

		parent::__construct($driver, $params);

		// Enable SQL debug log if configured
		if (SQL_DEBUG || ENABLE_PROFILER) {
			$this->callback = [$this, 'log'];
			$this->_log_start = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
		}
	}

	public function __destruct()
	{
		parent::__destruct();

		if (SQL_DEBUG && null !== $this->callback) {
			$this->saveLog();
		}
	}

	/**
	 * Disable logging if enabled
	 * useful to disable logging when reloading log page
	 */
	public function disableLog(): void {
		$this->callback = null;
		$this->_log_store = [];
	}

	public function getLog(): array
	{
		return $this->_log_store;
	}

	/**
	 * Saves the log in a different database at the end of the script
	 */
	protected function saveLog(): void
	{
		if (!count($this->_log_store)) {
			return;
		}

		$db = new SQLite3('sqlite', ['file' => SQL_DEBUG]);
		$db->exec('CREATE TABLE IF NOT EXISTS sessions (id INTEGER PRIMARY KEY, date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, script TEXT, user TEXT);
			CREATE TABLE IF NOT EXISTS log (session INTEGER NOT NULL REFERENCES sessions (id), time INTEGER, duration INTEGER, sql TEXT, trace TEXT);');

		$user = $_SESSION['userSession']->id ?? null;

		$db->insert('sessions', ['script' => Utils::getRequestURI() ?? str_replace(ROOT, '', $_SERVER['SCRIPT_NAME']), 'user' => $user]);
		$id = $db->lastInsertId();

		$db->begin();

		foreach ($this->_log_store as $row) {
			$db->insert('log', array_merge($row, ['session' => $id]));
		}

		$db->commit();
		$db->close();
	}

	/**
	 * Log current SQL query
	 */
	protected function log(string $method, ?string $timing, $object, ...$params): void
	{
		if ($method === '__destruct') {
			$this->_log_store[] = ['duration' => 0, 'time' => round((microtime(true) - $this->_log_start) * 1000 * 1000), 'sql' => null, 'trace' => null];
			return;
		}

		if ($method != 'execute' && $method != 'exec') {
			return;
		}

		if ($timing == 'before') {
			$this->_log_last = microtime(true);
			return;
		}

		$now = microtime(true);
		$duration = round(($now - $this->_log_last) * 1000 * 1000);
		$time = round(($now - $this->_log_start) * 1000 * 1000);

		if ($method == 'execute') {
			$sql = $params[0]->getSQL(true);
		}
		else {
			$sql = $params[0];
		}

		$sql = preg_replace('/^\s+/m', '  ', $sql);

		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
		$trace = '';

		foreach ($backtrace as $line) {
			if (!isset($line['file']) || in_array(basename($line['file']), ['DB.php', 'SQLite3.php']) || strstr($line['file'], 'lib/KD2')) {
				continue;
			}

			$file = isset($line['file']) ? str_replace(ROOT . '/', '', $line['file']) : '';

			$trace .= sprintf("%s:%d\n", $file, $line['line']);
		}

		$this->_log_store[] = compact('duration', 'time', 'sql', 'trace');
	}

	/**
	 * Return a debug log session using its ID
	 */
	static public function getDebugSession(int $id): ?\stdClass
	{
		$db = new SQLite3('sqlite', ['file' => SQL_DEBUG]);
		$s = $db->first('SELECT * FROM sessions WHERE id = ?;', $id);

		if ($s) {
			$s->list = $db->get('SELECT * FROM log WHERE session = ? ORDER BY time;', $id);

			foreach ($s->list as &$row) {
				try {
					$explain = DB::getInstance()->get('EXPLAIN QUERY PLAN ' . $row->sql);
					$row->explain = '';

					foreach ($explain as $e) {
						$row->explain .= $e->detail . "\n";
					}
				}
				catch (DB_Exception $e) {
					$row->explain = 'Error: ' . $e->getMessage();
				}
			}
		}

		$db->close();

		return $s;
	}

	/**
	 * Return the list of all debug sessions
	 */
	static public function getDebugSessionsList(): array
	{
		$db = new SQLite3('sqlite', ['file' => SQL_DEBUG]);
		$s = $db->get('SELECT s.*, SUM(l.duration) / 1000 AS sql_time, COUNT(l.rowid) AS count, MAX(l.time) / 1000 AS request_time
			FROM sessions s
			INNER JOIN log l ON l.session = s.id
			GROUP BY s.id
			ORDER BY s.date DESC;');

		$db->close();

		return $s;
	}

	public function disableInstallCheck(bool $disable)
	{
		$this->_install_check = !$disable;
	}

	public function connect(bool $check_installed = true): void
	{
		if (null !== $this->db) {
			return;
		}

		if ($check_installed && $this->_install_check && !self::isInstalled()) {
			throw new \LogicException('Database has not been installed!');
		}

		parent::connect();

		// Activer les contraintes des foreign keys
		$this->db->exec('PRAGMA foreign_keys = ON;');

		// 10 secondes
		$this->db->busyTimeout(10 * 1000);

		$mode = strtoupper(SQLITE_JOURNAL_MODE);
		$set_mode = $this->db->querySingle('PRAGMA journal_mode;');
		$set_mode = strtoupper($set_mode);

		if ($set_mode !== $mode) {
			// WAL = performance enhancement
			// see https://www.cs.utexas.edu/~jaya/slides/apsys17-sqlite-slides.pdf
			// https://ericdraken.com/sqlite-performance-testing/
			$this->exec(sprintf(
				'PRAGMA journal_mode = %s; PRAGMA synchronous = NORMAL; PRAGMA journal_size_limit = %d;',
				$mode,
				32 * 1024 * 1024
			));
		}

		self::registerCustomFunctions($this->db);
		self::toggleAuthorizer($this->db, true);
	}

	static public function toggleAuthorizer($db, bool $enable): void
	{
		if (!method_exists($db, 'setAuthorizer')) {
			return;
		}

		$db->setAuthorizer($enable ? [self::class, 'safetyAuthorizer'] : null);
	}

	/**
	 * Basic authorizer to make sure dangerous functions cannot be used:
	 * ATTACH, PRAGMA
	 */
	static public function safetyAuthorizer(int $action, ...$args)
	{
		if ($action === \SQLite3::ATTACH) {
			return \SQLite3::DENY;
		}

		if ($action === \SQLite3::PRAGMA) {
			// Only allow some PRAGMA statements
			static $allowed = ['integrity_check', 'foreign_key_check', 'application_id',
				'user_version', 'compile_options', 'legacy_alter_table', 'foreign_keys',
				'query_only', 'index_list', 'foreign_key_list', 'table_info',
				'index_xinfo',
			];

			if (!in_array($args[0], $allowed, true)) {
				return \SQLite3::DENY;
			}
		}

		return \SQLite3::OK;
	}

	static public function registerCustomFunctions($db)
	{
		$db->createFunction('dirname', [Utils::class, 'dirname']);
		$db->createFunction('basename', [Utils::class, 'basename']);
		$db->createFunction('unicode_like', [self::class, 'unicodeLike']);
		$db->createFunction('transliterate_to_ascii', [Utils::class, 'unicodeTransliterate']);
		$db->createFunction('email_hash', [Email::class, 'getHash']);
		$db->createFunction('md5', 'md5');
		$db->createFunction('uuid', [Utils::class, 'uuid']);
		$db->createFunction('random_string', [Utils::class, 'random_string']);
		$db->createFunction('print_binary', fn($value) => sprintf('%032d', decbin($value)));

		$db->createFunction('print_dynamic_field', function($name, $value) {
			$field = DynamicFields::get($name);

			if (!$field) {
				throw new DB_Exception(sprintf('There is no dynamic field with name "%s"', $name));
			}

			return $field->getStringValue($value);
		});

		$db->createFunction('match_dynamic_field', function($name, $value, ...$match) {
			if (empty($value)) {
				return null;
			}

			$field = DynamicFields::get($name);

			if (!$field) {
				throw new DB_Exception(sprintf('There is no dynamic field with name "%s"', $name));
			}

			if ($field->type === 'multiple') {
				if (!is_int($value)) {
					throw new DB_Exception(sprintf('The value "%s" is not an integer', $value));
				}

				$first = reset($match);

				if ($first === 'AND' || $first === 'OR') {
					array_shift($match);
				}
				else {
					$first = 'OR';
				}

				foreach ($match as $search) {
					$bit = array_search($search, $field->options, true);

					if ($bit === false) {
						throw new DB_Exception(sprintf('The option "%s" does not exist in field options', $search));
					}

					$found = $value & (1 << $bit);

					if ($first === 'OR' && $found) {
						return 1;
					}
					elseif ($first === 'AND' && !$found) {
						return null;
					}
				}

				return $first === 'AND' ? 1 : null;
			}

			return $match === $value;
		});

		$db->createCollation('U_NOCASE', [Utils::class, 'unicodeCaseComparison']);
		$db->createCollation('NAT_NOCASE', 'strnatcasecmp');
	}

	public function toggleUnicodeLike(bool $enable): void
	{
		if ($enable) {
			$this->createFunction('like', [$this, 'unicodeLike']);
		}
		else {
			// We should revert LIKE to the default, but we can't currently (FIXME?)
			// see https://github.com/php/php-src/issues/10726
			//$db->createFunction('like', null);
		}
	}

	public function version(): ?string
	{
		if (-1 === $this->_version) {
			$this->connect();
			$this->_version = self::getVersion($this->db);
		}

		return $this->_version;
	}

	static public function getVersion($db)
	{
		$v = (int) $db->querySingle('PRAGMA user_version;');

		if (empty($v)) {
			throw new \LogicException('Cannot find application version');
		}

		return self::parseVersion($v);
	}

	static public function parseVersion(int $v): ?string
	{
		if ($v > 0) {
			$major = intval($v / 1000000);
			$v -= $major * 1000000;
			$minor = intval($v / 10000);
			$v -= $minor * 10000;
			$release = intval($v / 100);
			$v -= $release * 100;
			$type = $v;

			if ($type == 0) {
				$type = '';
			}
			// Corrective release: 1.2.3.1
			elseif ($type > 75) {
				$type = '.' . ($type - 75);
			}
			// RC release
			elseif ($type > 50) {
				$type = '-rc' . ($type - 50);
			}
			// Beta
			elseif ($type > 25) {
				$type = '-beta' . ($type - 25);
			}
			// Alpha
			else {
				$type = '-alpha' . $type;
			}

			$v = sprintf('%d.%d.%d%s', $major, $minor, $release, $type);
		}

		return $v ?: null;
	}

	/**
	 * Save version to database
	 * rc, alpha, beta and corrective release (4th number) are limited to 24 versions each
	 * @param string $version Version string, eg. 1.2.3-rc2
	 */
	public function setVersion(string $version): void
	{
		if (!preg_match('/^(\d+)\.(\d+)\.(\d+)(?:(?:-(alpha|beta|rc)|\.)(\d+)|)?$/', $version, $match)) {
			throw new \InvalidArgumentException('Invalid version number: ' . $version);
		}

		$version = ((int)$match[1] * 100 * 100 * 100) + ((int)$match[2] * 100 * 100) + ((int)$match[3] * 100);

		if (isset($match[5])) {
			if ($match[5] > 24) {
				throw new \InvalidArgumentException('Invalid version number: cannot have a 4th component larger than 24: ' . $version);
			}

			if ($match[4] == 'rc') {
				$version += (int)$match[5] + 50;
			}
			elseif ($match[4] == 'beta') {
				$version += (int)$match[5] + 25;
			}
			elseif ($match[4] == 'alpha') {
				$version += (int)$match[5];
			}
			else {
				$version += (int)$match[5] + 75;
			}
		}

		$this->db->exec(sprintf('PRAGMA user_version = %d;', $version));
	}

	public function beginSchemaUpdate()
	{
		// Only start if not already taking place
		if ($this->_schema_update++ == 0) {
			$this->toggleForeignKeys(false);
			$this->begin();
		}
	}

	public function commitSchemaUpdate()
	{
		// Only commit if last call
		if (--$this->_schema_update == 0) {
			$this->commit();
			$this->toggleForeignKeys(true);
		}
	}

	public function lastErrorMsg()
	{
		return $this->db->lastErrorMsg();
	}

	/**
	 * @see https://www.sqlite.org/lang_altertable.html
	 */
	public function toggleForeignKeys(bool $enable): void
	{
		$this->connect();

		if (!$enable) {
			$this->db->exec('PRAGMA legacy_alter_table = ON;');
			$this->db->exec('PRAGMA foreign_keys = OFF;');

			if ($this->firstColumn('PRAGMA foreign_keys;')) {
				throw new \LogicException('Cannot disable foreign keys in an already started transaction');
			}
		}
		else {
			$this->db->exec('PRAGMA legacy_alter_table = OFF;');
			$this->db->exec('PRAGMA foreign_keys = ON;');
		}
	}

	/**
	 * This is a rewrite of SQLite LIKE function that is transforming
	 * the pattern and the value to lowercase ascii, so that we can match
	 * "émilie" with "emilie".
	 *
	 * This is probably not the best way to do that, but we have to resort to that
	 * as ICU extension is rarely available.
	 *
	 * @see https://www.sqlite.org/c3ref/strlike.html
	 * @see https://sqlite.org/src/file?name=ext/icu/icu.c&ci=trunk
	 */
	static public function unicodeLike($pattern, $value, $escape = null) {
		if (null === $pattern || null === $value) {
			return false;
		}

		$escape ??= '\\';
		$pattern = str_replace('’', '\'', $pattern); // Normalize French apostrophe
		$value = str_replace('’', '\'', $value);

		$id = md5($pattern . $escape);

		// Build regexp
		if (!array_key_exists($id, self::$unicode_patterns_cache)) {
			// Match escaped special chars | special chars | unicode characters | other
			$regexp = '/!([%_!])|([%_!])|(\pL+)|(.+?)/iu';
			$regexp = str_replace('!', preg_quote($escape, '/'), $regexp);

			preg_match_all($regexp, $pattern, $parts, PREG_SET_ORDER);
			$pattern = '';

			foreach ($parts as $part) {
				// Append other characters
				if (isset($part[4])) {
					$pattern .= preg_quote(strtolower($part[0]), '/');
				}
				// Append unicode
				elseif (isset($part[3])) {
					$pattern .= preg_quote(Utils::unicodeCaseFold($part[3]), '/');
				}
				// Append .*
				elseif (isset($part[2]) && $part[2] == '%') {
					$pattern .= '.*';
				}
				// Append .
				elseif (isset($part[2]) && $part[2] == '_') {
					$pattern .= '.';
				}
				// Append escaped special character
				else {
					$pattern .= preg_quote($part[1], '/');
				}
			}

			// Store pattern in cache
			$pattern = '/^' . $pattern . '$/im';
			self::$unicode_patterns_cache[$id] = $pattern;
		}

		$value = Utils::unicodeCaseFold($value);

		return (bool) preg_match(self::$unicode_patterns_cache[$id], $value);
	}

	public function dropIndexes(): void
	{
		foreach ($this->getAssoc('SELECT name, name FROM sqlite_master WHERE type = \'index\';') as $index) {
			if (preg_match('!^(?:sqlite_|plugin_|prv_)!', $index)) {
				continue;
			}

			$this->exec(sprintf('DROP INDEX IF EXISTS %s;', $index));
		}
	}
}
