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
		'db',
		'sql',
		'storage',
		'cron',
		'queue',
		'env',
		'ui',
		'server',
		'ext',
	];

	protected array $defaults = [];

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
						$i = $i ?: 1;
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
				if ($value !== false) {
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
			echo $this->color('red', vsprintf($message, $args));
			echo PHP_EOL;
		}

		exit(1);
	}

	protected function success(string $message = '', ...$args): void
	{
		if ($message !== '') {
			echo $this->color('green', vsprintf($message, $args));
			echo PHP_EOL;
		}

		exit(0);
	}

	protected function alert(string $message, ...$args): void
	{
		$message =  str_repeat('-', 50) . PHP_EOL . vsprintf($message, $args) . PHP_EOL . str_repeat('-', 50) . PHP_EOL;
		fwrite(STDERR, $this->color('yellow', $message));
	}

	protected function color(string $color, string $str): string
	{
		static $codes = [
			'red' => 91,
			'green' => 92,
			'yellow' => 93,
		];
		return sprintf("\e[%dm%s\e[0m", $codes[$color], $str);
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
	public function cron(): void
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
	 * Usage: paheko ext NAME SUBCOMMAND
	 *
	 * paheko ext list [--disabled]
	 *   List enabled extensions, use --disabled to only list disabled extensions.
	 *
	 * paheko ext NAME
	 *   Display informations on specified extension.
	 *
	 * paheko ext NAME enable
	 *   Enable the specified extension.
	 *
	 * paheko ext NAME disable
	 *   Disable the specified extension.
     *
	 * paheko ext NAME delete
	 *   Delete all extension data from database.
	 *   The extension needs to be disabled first.
	 *   (No confirmation asked!)
	 */
	public function ext(array $args): void
	{
		@list($name) = $this->parseOptions($args, [], 1);

		if (!$name) {
			$this->help(['ext']);
		}

		if ($name === 'list') {
			$o = $this->parseOptions($args, ['--disabled'], 0);
			$list = array_key_exists('disabled', $o) ? Extensions::listDisabled() : Extensions::listEnabled();

			foreach ($list as $ext) {
				echo $ext->name . PHP_EOL;
			}

			$this->success();
			return;
		}

		@list($command) = $this->parseOptions($args, [], 1);

		$ext = Extensions::get($name);

		if (!$ext) {
			$this->fail("This extension does not exist");
		}

		if (!$command) {
			printf("Name: %s\nType: %s\nEnabled: %s\nLabel: %s\nDescription: %s\n",
				$ext->name,
				$ext->type,
				$ext->enabled ? 'yes' : 'no',
				$ext->label,
				$ext->description
			);
		}
		elseif ($command === 'enable') {
			$ext->enable();
		}
		elseif ($command === 'disable') {
			$ext->disable();
		}
		elseif ($command === 'delete') {
			if ($ext->enabled) {
				$this->fail('This extension is enabled: cannot delete an enabled extension.');
			}

			$ext->delete();
		}
		else {
			$this->fail('Unknown subcommand: paheko ext %s %s', $name, $command);
		}

		$this->success();
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
	 * paheko queue run [--quiet|-q] [--force|-f]
	 *   Deliver messages waiting in the queue.
	 *   Will exit with status code 2 if there are still messages waiting in the queue.
	 *   If the queue is empty, the status code will be 0.
	 *   If --quiet is not specified, will print the number of messages sent, and still in queue.
	 *   If --force is specified, messages which have been marked for sending but failed,
	 *   will be sent now.
	 *
	 * paheko queue bounce
	 *   Read received bounce message from STDIN.
	 */
	public function queue(array $args): void
	{
		@list($command) = $this->parseOptions($args, [], 1);

		if (!$command) {
			$this->help(['queue']);
		}

		if ($command === 'count') {
			$count = Emails::countQueue();
			echo $count . PHP_EOL;
			$this->success();
		}
		elseif ($command === 'bounce') {
			$message = file_get_contents('php://stdin');

			if (empty($message)) {
				$this->fail('No STDIN content was provided. Please provide the email message on STDIN.');
			}

			Emails::handleBounce($message);
			$this->success();
		}
		elseif ($command === 'run') {
			$o = $this->parseOptions($args, ['--quiet|-q', '--force|-f'], 0);

			if (array_key_exists('force', $o)) {
				Emails::resetFailed(true);
			}

			// Send messages in queue
			$sent = Emails::runQueue();
			$count = Emails::countQueue();

			if (!array_key_exists('quiet', $o)) {
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

			$this->success();
		}
		else {
			$this->fail('Unknown subcommand: paheko queue %s', $command);
		}
	}

	/**
	 * Usage: paheko init OPTIONS…
	 * Create the local database using provided informations. If the database exists, an error will be returned.
	 *
	 * All options are mandatory.
	 *
	 * Options can also be passed as a list from STDIN. Example:
	 * cat <<<EOF
	 * country=FR
	 * orgname="My org"
	 * password="SECRET!!!"
	 * …
	 * EOF | bin/paheko init -
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
	 *     use --password-file instead if possible, or config file as STDIN
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
		foreach (Config::getInstance()->asArray() as $key => $value) {
			echo $key . ": " . Utils::var_export($value) . PHP_EOL;
		}
		$this->success();
	}

	/**
	 * Return local environment configuration.
	 */
	public function env(array $args)
	{
		foreach (Install::getConstants() as $key => $value) {
			echo $key . ": " . Utils::var_export($value) . PHP_EOL;
		}
		$this->success();
	}

	/**
	 * Usage: paheko db COMMAND
	 *
	 * paheko db backup FILE
	 *   Create a backup of the database to the provided file path.
	 *
	 * paheko db check
	 *   Check database integrity and foreign keys.
	 *
	 * paheko db fkfix
	 *   WARNING: this may result in data loss!
	 *   Will try to fix foreign keys issues by DELETING the rows pointing
	 *   to non-existing rows. Usually foreign key issues come from a parent
	 *   row having been deleted but not the linked rows. So it's often safe,
	 *   but you SHOULD make a backup before and verify with the 'check'
	 *   command.
	 */
	public function db(array $args)
	{
		@list($command) = $this->parseOptions($args, [], 1);
		$db = DB::getInstance();

		if ($command === 'check') {
			printf("Integrity: %s\n", $db->firstColumn('PRAGMA integrity_check;'));

			$fk = 0;

			foreach ($db->iterate('PRAGMA foreign_key_check;') as $row) {
				$fk++;
				echo $this->color('red', sprintf("Foreign key FAIL: %s:%d -> %s:%d", $row->table, $row->rowid, $row->parent, $row->fkid));
				echo PHP_EOL;
			}

			if (!$fk) {
				echo "Foreign keys: ok\n";
			}
			else {
				$this->fail("Foreign keys: %d rows failed!", $fk);
			}
		}
		elseif ($command === 'fkfix') {
			$db->begin();
			foreach ($db->iterate('PRAGMA foreign_key_check;') as $row) {
				$db->delete($row->table, 'id = ' . (int)$row->rowid);
			}
			$db->commit();
		}
		elseif ($command === 'backup') {
			@list($file) = $this->parseOptions($args, [], 1);
			Backup::make($file);
		}
		else {
			$this->help(['db']);
		}

		$this->success();
	}

	/**
	 * Usage: paheko sql STATEMENT
	 *   Run SQL statement and display result.
	 *   Only read-only queries are supported (SELECT).
	 *   INSERT, CREATE, ALTER, and other queries that would change the database are not supported.
	 */
	public function sql(array $args)
	{
		$rw = false;

		// This is a hidden parameter, by design! **DO NOT USE THIS!**
		// This is intended to be used in *VERY* specific cases,
		// mainly for debug use.
		// You shouldn't modify the database yourself!
		if (($args[0] ?? null) === '--write') {
			$rw = true;
			unset($args[0]);
		}

		$sql = implode(' ', $args);

		if (trim($sql) === '') {
			$this->fail('No statement was provided.');
		}

		$db = DB::getInstance();

		if ($rw) {
			echo "[WRITE] " . $sql . PHP_EOL;
			$st = $db->prepare($sql);
		}
		else {
			echo "[SQL] " . $sql . PHP_EOL;
			$st = $db->protectSelect(null, $sql);
		}

		$r = $st->execute();

		if ($rw) {
			$r->finalize();
			printf("%d row(s) changed\n", $db->changes());
			$this->success();
			return;
		}

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

	/**
	 * Usage: paheko server OPTIONS...
	 * Launch a web server for Paheko on the specified port and IP address.
	 *
	 * By default, 127.0.0.1 and port 8081 are used if nothing is specified.
	 *
	 * Options:
	 *   --bind|-b ADDRESS
	 *     IP address of the web server
	 *
	 *   --port|-p PORT
	 *     Port of the web server
	 *
	 *   --verbose|-v
	 *     If this option is specified, the web server requests will be logged
	 *     on the terminal.
	 */
	public function server(array $args, bool $browser = false): void
	{
		$o = $this->parseOptions($args, ['--port|-p=', '--bind|-b=', '--verbose|-v'], 0);

		$address = $o['bind'] ?? '127.0.0.1';
		$port = intval($o['port'] ?? 8081);
		$verbose = array_key_exists('verbose', $o);
		$root = ROOT . '/www';
		$router = $root . '/_route.php';

		$env = '';

		// Pass command-line constants as environment variables
		foreach ($this->defaults as $key => $value) {
			$env .= sprintf('PAHEKO_%s=%s ', $key, escapeshellarg($value));
		}

		$cmd = sprintf('%s PHP_CLI_SERVER_WORKERS=3 php -S %s:%d -t %s -d variables_order=EGPCS %s',
			$env,
			escapeshellarg($address),
			$port,
			$root,
			$router
		);

		$launched = false;

		if ($browser) {
			$url = sprintf('http://%s:%d/admin/', $address, $port);

			if (($_SERVER['DISPLAY'] ?? '') !== '') {
				$browser = 'sensible-browser %s &';
			}
			else {
				$browser = 'www-browser %s';
			}

			$browser = sprintf($browser, escapeshellarg($url));
		}
		else {
			$browser = null;
		}

		$print = function ($str) use ($verbose, &$browser) {
			if ($verbose) {
				echo $str;
			}

			if ($browser) {
				passthru($browser);
				$browser = null;
			}
		};

		Utils::exec($cmd, 0, null, $print, $print);
	}

	/**
	 * Usage: paheko ui OPTIONS...
	 * Launch a web server for Paheko on the specified port and IP address,
	 * and open a web browser directly.
	 *
	 * By default, 127.0.0.1 and port 8081 are used if nothing is specified.
	 *
	 * Options:
	 *   --bind|-b ADDRESS
	 *     IP address of the web server
	 *
	 *   --port|-p PORT
	 *     Port of the web server
	 *
	 *   --verbose|-v
	 *     If this option is specified, the web server requests will be logged
	 *     on the terminal.
	 */
	public function ui(array $args)
	{
		$this->server($args, true);
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
			$this->fail('Unknown command "%s".' . PHP_EOL . 'Use "paheko help" to get list of commands.', $command);
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
			$this->fail('Unknown command "%s".' . PHP_EOL . 'Use "paheko help" to get list of commands.', $command);
		}

		$constants = [];
		$root = dirname(__DIR__, 3);

		if (isset($options['config'])) {
			$constants['CONFIG_FILE'] = $options['config'];
		}

		if (isset($options['db'])) {
			$constants['DB_FILE'] = $options['db'];
		}

		if (isset($options['root'])) {
			$constants['DATA_ROOT'] = $options['root'];
		}

		foreach ($options as $name => $value) {
			if (substr($name, 0, 1) === 'D') {
				$constants[substr($name, 1)] = $value;
			}
		}

		$this->defaults = $constants;

		$constants['SKIP_STARTUP_CHECK'] = true;

		foreach ($constants as $name => $value) {
			define('Paheko\\' . $name, $value);
		}

		// Make sure we have a host/root to specify if
		// WWW_URL/WWW_URI are not specified
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['DOCUMENT_ROOT'] = $root . '/www';

		require_once $root . '/include/init.php';

		if (WWW_URL === 'http://localhost/' && $command !== 'ui') {
			$this->alert("Warning: WWW_URL constant is not specified!\nhttp://localhost/ will be used instead.\n"
				. "Any e-mail sent will not include the correct web server URL.");
		}

		if (!in_array($command, ['help', 'init', 'ui', 'server', 'db'])) {
			if (!DB::isInstalled()) {
				$this->fail('Database does not exist. Run "init" command first.');
			}

			if ($command !== 'upgrade' && DB::isUpgradeRequired()) {
				$this->fail('The database requires an upgrade. Run "upgrade" command first.');
			}
		}

		try {
			$this->$command($args);
		}
		catch (UserException $e) {
			$this->fail($e->getMessage());
		}
	}
}
