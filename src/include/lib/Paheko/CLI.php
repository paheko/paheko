<?php

namespace Paheko;

use Paheko\Files\Storage;
use Paheko\Entities\Files\File;

use Paheko\Services\Reminders;
use Paheko\Files\Files;
use Paheko\Files\Shares;
use Paheko\Files\Trash;

use Paheko\Install;
use Paheko\Upgrade;
use Paheko\Utils;
use Paheko\UserException;

use Paheko\Email\Emails;

class CLI
{
	const COMMANDS = [
		'help',
		'init',
		'upgrade',
		'version',
		'config',
		'sql',
		'storage',
		'cron',
		'queue',
	];

	public function parseOptions(array &$args, array $options, int $limit = 0)
	{
		$all_aliases = [];

		foreach ($options as &$opt) {
			if (!preg_match('/^((?:-[a-z]|--[a-z-]+)\*?)((?:\|(?:-[a-z]|--[a-z-]+)\*?)*)(=?)$/i', $opt, $match)) {
				throw new \InvalidArgumentException('Invalid option: ' . $opt);
			}
			$aliases = [$match[1]];

			if (isset($match[2])) {
				$aliases = array_merge($aliases, explode('|', substr($match[2], 1)));
			}

			$opt = [
				'value'   => !empty($match[3]),
				'name'    => preg_replace('/^-+/', '', $match[1]),
				'aliases' => $aliases,
			];

			foreach ($aliases as $alias) {
				$all_aliases[$alias] =& $opt;
			}
		}

		unset($opt);

		$out = [];
		$commands = [];
		$current = null;
		$i = 0;

		foreach ($args as $i => $arg) {
			if (null !== $current) {
				$out[$current] = $arg;
				$current = null;
				continue;
			}

			if (substr($arg, 0, 1) !== '-') {
				if (count($commands) < $limit) {
					$commands[] = $arg;

					if (count($commands) === $limit) {
						break;
					}

					continue;
				}
				else {
					break;
				}
			}

			$name = strtok($arg, '=');
			$value = strtok('');
			$opt = $all_aliases[$name] ?? null;

			if (!$opt) {
				$this->fail('Unknown option: %s', $name);
			}

			if ($opt['value']) {
				if ($value === false) {
					$current = $opt['name'];
					continue;
				}
				else {
					$out[$opt['name']] = $value;
				}
			}
			else {
				if (!$opt['value']) {
					$this->fail('Options "%s" does not allow for a value', $name);
				}

				$out[$opt['name']] = null;
			}
		}

		foreach ($commands as $command) {
			$out[] = $command;
		}

		$args = array_slice($args, $i);

		return $out;
	}

	protected function fail(string $message = '', ...$args): void
	{
		if ($message !== '') {
			vprintf($message, $args);
			echo PHP_EOL;
		}

		exit(1);
	}

	protected function success(string $message = '', ...$args): void
	{
		if ($message !== '') {
			vprintf($message, $args);
			echo PHP_EOL;
		}

		exit(0);
	}

	/**
	 * Upgrade the local database to the currently installed version.
	 */
	public function upgrade(array $args): void
	{
		if (Upgrade::preCheck()) {
			Upgrade::upgrade();
			exit(2);
		}
		else {
			exit(0);
		}
	}

	/**
	 * Run daily cron tasks.
	 */
	public function cron(array $args): void
	{
		$config = Config::getInstance();

		if ($config->backup_frequency && $config->backup_limit) {
			Backup::auto();
		}

		// Send pending reminders
		Reminders::sendPending();

		if (Files::getVersioningPolicy() !== 'none') {
			Files::pruneOldVersions();
		}

		// Make sure we are cleaning the trash
		Trash::clean();

		// Remove expired file sharing links
		Shares::prune();

		Plugins::fire('cron');

	}

	/**
	 * Usage: paheko storage SUBCOMMAND
	 *
	 * paheko storage import
	 *   Import files from configured storage to database
	 *
	 * paheko storage export
	 *   Export files from database to configured storage
	 *
	 * paheko storage truncate
	 *   Delete all files contents from database.
	 *   (No confirmation asked!)
     *
	 * paheko storage scan
	 *   Update or rebuild files list in database by listing files
	 *   directly from configured storage.
	 */
	public function storage(array $args): void
	{
		if (FILE_STORAGE_BACKEND === 'SQLite' || !FILE_STORAGE_CONFIG) {
			$this->fail('Invalid: FILE_STORAGE_BACKEND is \'SQLite\' or FILE_STORAGE_CONFIG is not set');
		}

		@list($command) = $this->parseOptions($args, [], 1);

		if (!$command) {
			$this->help(['storage']);
		}

		$callback = fn (string $action, File $file) => printf("%s: %s\n", $action, $file->path);

		if ($command === 'import') {
			Storage::migrate(FILE_STORAGE_BACKEND, 'SQLite', FILE_STORAGE_CONFIG, null, $callback);
		}
		elseif ($command === 'export') {
			Storage::migrate('SQLite', FILE_STORAGE_BACKEND, null, FILE_STORAGE_CONFIG, $callback);
		}
		elseif ($command === 'truncate') {
			Storage::truncate('SQLite', null);
			print("Deleted all files contents from database.\n");
		}
		elseif ($command === 'scan') {
			Storage::sync(null, $callback);
		}
		else {
			$this->fail('Unknown subcommand: paheko storage %s', $command);
		}

		$this->success();
	}

	/**
	 * Usage: paheko queue SUBCOMMAND ARGS…
	 *
	 * paheko queue count
	 *   Display number of messages in e-mail queue.
	 *
	 * paheko queue run [--quiet|-q]
	 *   Deliver messages waiting in the queue.
	 *   Will exit with status code 2 if there are still messages waiting in the queue.
	 *   If the queue is empty, the status code will be 0.
	 */
	public function queue(array $args): void
	{
		@list($command) = $this->parseOptions($args, [], 1);

		if (!$command) {
			$this->help(['queue']);
		}

		$count = Emails::countQueue();

		if ($command === 'count') {
			echo $count . PHP_EOL;
			$this->success();
		}
		elseif ($command === 'run') {
			var_dump($command, $args); exit;
			$o = $this->parseOptions($args, ['--quiet|-q'], 0);

			// Send messages in queue
			$sent = Emails::runQueue();

			if (array_key_exists('quiet', $o)) {
				if ($sent) {
					printf("%d messages sent\n", $sent);
				}

				if ($count) {
					printf("%d messages still in queue\n", $count);
				}
			}

			if ($count) {
				exit(2);
			}

			exit(0);
		}
	}

	/**
	 * Usage: paheko init OPTIONS…
	 * Create the local database using provided informations. If the database exists, an error will be returned.
	 *
	 * All options are mandatory.
	 *
	 * Options:
	 *   --country CODE
	 *     Organization country code (2 letters, eg. FR, BE…)
	 *
	 *   --orgname NAME
	 *     Organization name
	 *
	 *   --name NAME
	 *     User name
	 *
	 *   --email EMAIL
	 *     User e-mail address (will also be set as the organization e-mail)
	 *
	 *   --password PASSWORD
	 *     User password (NOT RECOMMENDED, as the password can leak in your history)
	 *     use --password-file instead if possible, or config file as STDIN'
	 *
	 *   --password-file FILE
	 *     Path to a file containing the user password
	 */
	public function init(array $args): void
	{
		$o = $this->parseOptions($args, ['--country=', '--orgname=', '--name=', '--email=', '--password=', '--password-file='], 1);

		if (($o[0] ?? null) === '-') {
			$lines = stream_get_contents(STDIN);

			if (!empty($lines)) {
				$o = Utils::parse_ini_string($lines, false);
			}
		}

		if (isset($o['password-file'])) {
			$o['password'] = trim(file_get_contents($o['password-file']));
		}

		foreach (['country', 'orgname', 'name', 'email', 'password'] as $key) {
			if (empty($o[$key])) {
				$this->fail('Missing option: --%s', $key);
			}
		}

		if (file_exists(DB_FILE)) {
			$this->fail('Database file already exists');
		}

		try {
			Install::install($o['country'], $o['orgname'], $o['name'], $o['email'], $o['password']);
			$this->success();
		}
		catch (\Throwable $e) {
			Utils::safe_unlink(DB_FILE);
			throw $e;
		}
	}

	/**
	 * Return database version.
	 */
	public function version(array $args)
	{
		echo DB::getInstance()->version() . PHP_EOL;
		$this->success();
	}

	/**
	 * Return organization configuration.
	 */
	public function config(array $args)
	{
		echo json_encode(Config::getInstance()->asArray(), JSON_PRETTY_PRINT) . PHP_EOL;
		$this->success();
	}

	/**
	 * Usage: paheko sql OPTIONS… STATEMENT
	 * Run SQL statement and display result.
	 *
	 * Options:
	 *   --rw
	 *     Specify this option to allow the statement to modify the database.
	 *     If this is absent, the statement will be executed in read-only.
	 */
	public function sql(array $args)
	{
		$rw = false;

		if ($args[0] === '--rw') {
			$rw = true;
			unset($args[0]);
		}

		$sql = implode(' ', $args);

		if (trim($sql) === '') {
			$this->fail('No statement was provided.');
		}

		$db = DB::getInstance();

		if ($rw) {
			echo "[RW] " . $sql . PHP_EOL;
			$st = $db->prepare($sql);
		}
		else {
			echo "[RO] " . $sql . PHP_EOL;
			$st = $db->protectSelect(null, $sql);
		}

		$r = $st->execute();

		$columns = [];

		for ($i = 0; $i < $r->numColumns(); $i++) {
			$columns[$i] = $r->columnName($i);
		}

		while ($row = $r->fetchArray(\SQLITE3_NUM)) {
			foreach ($row as $i => $v) {
				echo $columns[$i] . ": " . $v . PHP_EOL;
			}

			echo str_repeat('-', 70) . PHP_EOL;
		}

		$this->success();
	}

	public function help(array $args)
	{
		@list($command) = $this->parseOptions($args, [], 1);

		if (!$command) {
			echo "Usage: paheko OPTIONS… COMMAND\n\n";
			echo "Global options:\n";
			echo "  --config|-c		Path to config file\n";
			echo "  --db			Path to database file\n";
			echo "  --root			Path to data root\n";
			echo "  --url			URL of instance\n";
			echo "  -DCONSTANT		Specify configuration constants, eg. -DDISABLE_EMAIL=true\n\n";
			echo "Usage: fossil help COMMAND\n";
			echo "Available commands: " . implode(' ', self::COMMANDS) . "\n\n";
			exit(0);
		}

		if (!in_array($command, self::COMMANDS)) {
			$this->fail('Unknown command "%s". Use "paheko help" to get list of commands.', $command);
		}

		$ref = new \ReflectionMethod(self::class, $command);
		$comment = $ref->getDocComment();
		$comment = preg_replace('!^\h*\*\h?!m', '', trim($comment, "\r\n\t/* "));
		echo $comment;
		echo PHP_EOL;
		$this->success();
	}

	public function run(array $args): void
	{
		$options = $this->parseOptions($args, ['--config|-c=', '--db=', '--root=', '--url=', '-D*='], 1);
		$command = $options[0] ?? null;

		if (empty($command)) {
			$this->fail('No command was passed. Use "paheko help" to get list of commands.');
		}

		if (!in_array($command, self::COMMANDS)) {
			$this->fail('Unknown command "%s". Use "paheko help" to get list of commands.', $command);
		}

		$config = $options['config'] ?? null;

		if (isset($config)) {
			define('\Paheko\CONFIG_FILE', $config);
		}

		$constants = [];

		if (isset($options['db'])) {
			$constants['DB_FILE'] = $options['db'];
		}

		if (isset($options['root'])) {
			$constants['ROOT'] = $options['root'];
		}

		foreach ($options as $name => $value) {
			if (substr($name, 0, 1) === 'D') {
				$constants[substr($name, 1)] = $value;
			}
		}

		foreach ($constants as $name => $value) {
			define('Paheko\\' . $name, $value);
		}


		define('Paheko\INSTALL_PROCESS', true);

		require_once __DIR__ . '/../../init.php';

		try {
			$this->$command($args);
		}
		catch (UserException $e) {
			$this->fail($e->getMessage());
		}
	}
}
