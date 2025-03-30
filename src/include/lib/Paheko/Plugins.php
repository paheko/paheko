<?php

namespace Paheko;

use Paheko\Entities\Module;
use Paheko\Entities\Plugin;
use Paheko\Entities\Signal;

use Paheko\Users\Session;
use Paheko\DB;
use Paheko\UserTemplate\Modules;

use KD2\DB\EntityManager as EM;
use KD2\ErrorManager;

use const Paheko\{SYSTEM_SIGNALS, ADMIN_URL, WWW_URL, PLUGINS_ROOT, HOSTING_PROVIDER, PLUGINS_ALLOWLIST, PLUGINS_BLOCKLIST};

class Plugins
{
	const NAME_REGEXP = '[a-z][a-z0-9]*(?:_[a-z0-9]+)*';

	const MIME_TYPES = [
		'css'  => 'text/css',
		'gif'  => 'image/gif',
		'htm'  => 'text/html',
		'html' => 'text/html',
		'ico'  => 'image/x-ico',
		'jpe'  => 'image/jpeg',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'js'   => 'application/javascript',
		'pdf'  => 'application/pdf',
		'png'  => 'image/png',
		'xml'  => 'text/xml',
		'svg'  => 'image/svg+xml',
		'webp' => 'image/webp',
		'md'   => 'text/x-markdown',
	];

	/**
	 * Set to false to disable signal firing
	 * @var boolean
	 */
	static protected $signals = true;

	static public function toggleSignals(bool $enabled)
	{
		self::$signals = $enabled;
	}

	static public function getPrivateURL(string $id, string $path = '')
	{
		return ADMIN_URL . 'p/' . $id . '/' . ltrim($path, '/');
	}

	static public function getPublicURL(string $id, string $path = '')
	{
		return WWW_URL . 'p/' . $id . '/' . ltrim($path, '/');
	}

	static public function getPath(string $name): ?string
	{
		if (file_exists(PLUGINS_ROOT . '/' . $name)) {
			return PLUGINS_ROOT . '/' . $name;
		}
		elseif (file_exists(PLUGINS_ROOT . '/' . $name . '.tar.gz')) {
			return 'phar://' . PLUGINS_ROOT . '/' . $name . '.tar.gz';
		}

		return null;
	}

	static public function routeStatic(string $name, string $uri): bool
	{
		$path = self::getPath($name);

		if (!$path) {
			throw new \RuntimeException('Invalid plugin: ' . $name);
		}

		if (!preg_match('!^(?:public/|admin/)!', $uri) || false !== strpos($uri, '..')) {
			return false;
		}

		$path .= '/' . $uri;

		if (!file_exists($path)) {
			return false;
		}

		// Récupération du type MIME à partir de l'extension
		$pos = strrpos($path, '.');
		$ext = substr($path, $pos+1);

		$mime = self::MIME_TYPES[$ext] ?? 'text/plain';

		header('Content-Type: ' .$mime);
		header('Cache-Control: public, max-age=3600');
		header('Last-Modified: ' . date(DATE_RFC7231, filemtime($path)));

		// Don't return Content-Length on OVH, as their HTTP 2.0 proxy is buggy
		// @see https://fossil.kd2.org/paheko/tktview/8b342877cda6ef7023b16277daa0ec8e39d949f8
		if (HOSTING_PROVIDER !== 'OVH') {
			header('Content-Length: ' . filesize($path));
		}

		readfile($path);
		return true;
	}

	static public function exists(string $name): bool
	{
		return self::getPath($name) !== null;
	}

	static public function hasSignal(string $name): bool
	{
		foreach (SYSTEM_SIGNALS as $system_signal) {
			if (key($system_signal) === $name) {
				return true;
			}
		}

		return (bool) DB::getInstance()->firstColumn('SELECT 1 FROM plugins_signals AS s INNER JOIN plugins p ON p.name = s.plugin
			WHERE s.signal = ? AND p.enabled = 1 LIMIT 1;', $name);
	}

	/**
	 * Fire a plugin signal
	 * @param  string $name      Signal name
	 * @param  bool   $stoppable Set to TRUE if the signal can be stopped
	 * @param  array  $in        Set to a list of INcoming parameters
	 * @param  array  $out       Set to a list of possible OUTgoing parameters (callbacks can still set any other keys in this array, just they might not be used then)
	 * @return Signal|null Signal if a signal was run, null if no signal was registered
	 */
	static public function fire(string $name, bool $stoppable = false, array $in = [], array $out = []): ?Signal
	{
		if (!self::$signals) {
			return null;
		}

		$signal = null;

		// Process SYSTEM_SIGNALS first
		foreach (SYSTEM_SIGNALS as $system_signal) {
			if (key($system_signal) != $name) {
				continue;
			}

			if (!is_callable(current($system_signal))) {
				throw new \LogicException(sprintf('System signal: cannot call "%s" for signal "%s"', current($system_signal), $name));
			}

			$signal ??= new Signal($name, $stoppable, $in, $out);

			call_user_func(current($system_signal), $signal, null);

			if ($signal->isStopped()) {
				return $signal;
			}
		}

		$list = DB::getInstance()->iterate('SELECT s.* FROM plugins_signals AS s INNER JOIN plugins p ON p.name = s.plugin
			WHERE s.signal = ? AND p.enabled = 1;', $name);

		static $plugins = [];

		foreach ($list as $row) {
			if (!self::isAllowed($row->plugin)) {
				continue;
			}

			$plugins[$row->plugin] ??= Plugins::get($row->plugin);
			$plugin = $plugins[$row->plugin];

			// Don't call plugins when the code has vanished
			if (!$plugin->hasCode()) {
				continue;
			}

			$callback = 'Paheko\\Plugin\\' . $row->callback;

			// Ignore non-callable plugins
			if (!is_callable($callback)) {
				ErrorManager::reportExceptionSilent(new \LogicException(sprintf(
					'Plugin has registered signal "%s" but callback "%s" is not a callable',
					$name,
					$callback
				)));
				continue;
			}

			$signal ??= new Signal($name, $stoppable, $in, $out);

			call_user_func($callback, $signal, $plugin);

			if ($signal->isStopped()) {
				return $signal;
			}
		}

		return $signal;
	}

	static public function get(string $name): ?Plugin
	{
		return EM::findOne(Plugin::class, 'SELECT * FROM @TABLE WHERE name = ?;', $name);
	}

	static public function listInstalled(): array
	{
		$list = EM::getInstance(Plugin::class)->all('SELECT * FROM @TABLE ORDER BY label COLLATE NOCASE ASC;');

		foreach ($list as $key => $p) {
			try {
				$p->selfCheck();
			}
			catch (ValidationException $e) {
				if (self::removeBroken($p->name)) {
					unset($list[$key]);
				}
				else {
					$p->setBrokenMessage($e->getMessage());
				}
			}
		}

		return $list;
	}

	/**
	 * Remove old/broken extensions if they are still installed somehow
	 */
	static public function removeBroken(string $name): bool
	{
		if ($name === 'garradin_eu') {
			DB::getInstance()->exec('
				DELETE FROM plugins_signals WHERE plugin = \'garradin_eu\';
				DELETE FROM plugins WHERE name = \'garradin_eu\';
			');
			return true;
		}

		return false;
	}

	static public function refresh(): array
	{
		$db = DB::getInstance();
		$existing = $db->getAssoc(sprintf('SELECT id, name FROM %s;', Plugin::TABLE));
		$errors = [];

		foreach ($existing as $name) {
			$f = self::get($name);
			try {
				$f->updateFromINI();
				$f->save();
			}
			catch (ValidationException $e) {
				$errors[] = $name . ': ' . $e->getMessage();
			}
		}

		return $errors;
	}

	static public function getInstallable(string $name): ?Plugin
	{
		if (!file_exists(PLUGINS_ROOT . '/' . $name) && !file_exists(PLUGINS_ROOT . '/' . $name . '.tar.gz')) {
			return null;
		}

		$p = new Plugin;
		$p->name = $name;

		if (!$p->hasFile($p::META_FILE)) {
			throw new UserException(sprintf('Le plugin "%s" n\'est pas une extension Paheko : fichier plugin.ini manquant.', $name));
		}

		$p->updateFromINI();

		try {
			$p->selfCheck();
		}
		catch (ValidationException $e) {
			$p->setBrokenMessage($e->getMessage());
		}

		return $p;
	}

	/**
	 * Liste les plugins téléchargés mais non installés
	 */
	static public function listInstallable(bool $check_exists = true): array
	{
		$list = [];

		if ($check_exists) {
			$exists = DB::getInstance()->getAssoc('SELECT name, name FROM plugins;');
		}
		else {
			$exists = [];
		}

		foreach (glob(PLUGINS_ROOT . '/*') as $file)
		{
			if (substr($file, 0, 1) == '.') {
				continue;
			}

			if (is_dir($file) && file_exists($file . '/' . Plugin::META_FILE)) {
				$file = basename($file);
				$name = $file;
			}
			elseif (substr($file, -7) == '.tar.gz' && file_exists('phar://' . $file . '/' . Plugin::META_FILE)) {
				$file = basename($file);
				$name = substr($file, 0, -7);
			}
			else {
				continue;
			}

			if (!self::isAllowed($name)) {
				continue;
			}

			// Ignore existing plugins
			if (in_array($name, $exists)) {
				continue;
			}

			$list[$name] = self::getInstallable($name);
		}

		ksort($list);

		return $list;
	}

	static public function isAllowed(string $name): bool
	{
		// Ignore plugin if not in whitelist
		if (is_array(PLUGINS_ALLOWLIST) && !in_array($name, PLUGINS_ALLOWLIST, true)) {
			return false;
		}

		// Ignore plugin if in blacklist
		if (is_array(PLUGINS_BLOCKLIST) && in_array($name, PLUGINS_BLOCKLIST, true)) {
			return false;
		}

		return true;
	}

	/**
	 * Upgrade all plugins if required
	 * This is run after an upgrade, a database restoration, or in the Plugins page
	 */
	static public function upgradeAllIfRequired(): bool
	{
		$i = 0;

		foreach (self::listInstalled() as $plugin) {
			// Ignore plugins if code is no longer available
			if (!$plugin->isAvailable() || !self::isAllowed($plugin->name)) {
				continue;
			}

			if ($plugin->needUpgrade()) {
				$plugin->upgrade();
				$i++;
			}

			unset($plugin);
		}

		return $i > 0;
	}
}
