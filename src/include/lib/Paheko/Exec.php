<?php
declare(strict_types=1);

namespace Paheko;

class Exec
{
	protected ?string $cmd;
	protected ?string $stdin_str = null;
	protected ?string $stdout = null;
	protected ?string $stderr = null;

	protected array $args = [];

	protected array $binds = [
		'/bin'              => false,
		'/usr'              => false,
		'/lib'              => false,
		'/lib64'            => false,
		// Required for Java
		'/etc/alternatives' => false,
		SHARED_CACHE_ROOT   => false,
		// Required for chromium + for reading uploaded files
		'/tmp'              => true,
		CACHE_ROOT          => true,
	];

	const PDF_COMMANDS = [
		'chromium',
		'prince',
		'weasyprint',
	];

	protected bool $network = false;
	protected bool $print_stdout = false;
	protected int $timeout = 5;

	static public function quick(string $cmd, int $timeout = 20, ?int &$code = null): ?string
	{
		$e = new self($cmd);
		$e->setTimeout($timeout);
		$code = $e->run();
		return $e->getStdout();
	}

	public function __construct(?string $cmd = null, int $timeout = 5)
	{
		if (FILE_STORAGE_BACKEND === 'FileSystem') {
			// Allow access to locally stored files, but read-only
			$this->binds[FILE_STORAGE_CONFIG] = false;
		}

		$this->cmd = $cmd;
		$this->timeout = $timeout;
	}

	public function toggleNetworkAccess(bool $enable): void
	{
		$this->network = $enable;
	}

	public function togglePrintStdout(bool $enable): void
	{
		$this->print_stdout = $enable;
	}

	public function setStdin(string $str): void
	{
		$this->stdin_str = $str;
	}

	public function setTimeout(int $timeout): void
	{
		$this->timeout = $timeout;
	}

	public function setCommand(string $str): void
	{
		$this->cmd = $str;
	}

	public function addParam(string $str): void
	{
		$this->cmd .= ' ' . $str;
	}

	public function addParams(array $params): void
	{
		foreach ($params as $param) {
			$this->addParam($param);
		}
	}

	public function addBind(string $path, bool $write = false): void
	{
		$this->binds[$path] = $write;
	}

	public function run(bool $throw_exception = false, bool $unsafe = false): int
	{
		$cmd = $this->cmd;

		if (!$unsafe && !empty(EXECUTION_JAIL)) {
			$cmd = $this->getSandboxCommand() . ' ' . $cmd;
		}

		$this->stdout = null;
		$this->stderr = null;

		$code = Utils::exec($cmd, $this->timeout, $this->stdin_str,
			function ($data) {
				if ($this->print_stdout) {
					echo $data;
				}
				else {
					$this->stdout ??= '';
					$this->stdout .= $data;
				}
			},
			function ($data) {
				$this->stderr ??= '';
				$this->stderr .= $data;
			}
		);

		if ($code && $throw_exception) {
			$msg = $this->stderr ?? '';
			$msg .= "\n" . ($this->stdout ?? '');
			$msg = trim($msg);

			if ($msg === '') {
				$msg = 'Command failed with code ' . $code;
			}

			$msg .= "\n→ " . $cmd;

			throw new \RuntimeException($msg, $code);
		}

		return $code;
	}

	public function getStdout(): ?string
	{
		return $this->stdout;
	}

	public function getStderr(): ?string
	{
		return $this->stderr;
	}

	public function hasCommand(): bool
	{
		return !empty($this->cmd);
	}

	public function getCommand(): string
	{
		return $this->cmd;
	}

	protected function getSandboxCommand(): ?string
	{
		if (EXECUTION_JAIL !== 'bubblewrap') {
			throw new \LogicException('Unknown execution jail: ' . EXECUTION_JAIL);
		}

		$binds = $this->binds;

		// Use Bubblewrap to jail running apps
		// https://jvns.ca/blog/2022/06/28/some-notes-on-bubblewrap/
		// In some distant future, using nsjail might be better (more options: timeout, network),
		// but it's not in Debian yet, see https://bugs.debian.org/964199
		$args = [
			'--clearenv',
			'--new-session',
			'--die-with-parent',
			'--unshare-all',
			'--hostname local',
			'--proc /proc',
			'--dev /dev',
			sprintf('--chdir %s', escapeshellarg(realpath(CACHE_ROOT))),
		];

		if ($this->network) {
			$args[] = '--share-net';
		}

		$name = strtok($this->cmd, ' ');
		strtok('');

		if (in_array($name, self::PDF_COMMANDS)) {
			// Required for prince, Chromium, etc. for font config
			$binds['/etc/fonts'] = false;
			$binds['/var/cache/fontconfig'] = true;

			if ($name === 'prince' && PRINCE_LICENSE_FILE) {
				$binds[PRINCE_LICENSE_FILE] = false;
			}

			// Only allow network access when required (PDF processors)
			// TODO: restrict network access to some vhosts, see https://jvns.ca/blog/2022/06/28/some-notes-on-bubblewrap/
			if (!$this->network) {
				$args[] = '--share-net';
			}
		}

		if ($name === 'chromium') {
			$binds['/etc/chromium.d'] = false;
		}

		// Sort binds in order
		asort($binds, SORT_STRING);

		foreach ($binds as $path => $write) {
			if (!is_readable($path)) {
				throw new \LogicException('Cannot read bind path: ' . $path);
			}
			$args[] = sprintf('%s %s %2$s', $write ? '--bind' : '--ro-bind', escapeshellarg($path));
		}

		return 'bwrap ' . implode(' ', $args);
	}
}