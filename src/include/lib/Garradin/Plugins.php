<?php

namespace Garradin;

use Garradin\Entities\Plugin;
use Garradin\Entities\Module;

use Garradin\Users\Session;
use Garradin\DB;
use Garradin\UserTemplate\CommonFunctions;
use Garradin\UserTemplate\Modules;

use \KD2\DB\EntityManager as EM;

use const Garradin\{SYSTEM_SIGNALS, ADMIN_URL};

class Plugins
{
	const NAME_REGEXP = '[a-z][a-z0-9]*(?:_[a-z0-9]+)*';

	/**
	 * Set to false to disable signal firing
	 * @var boolean
	 */
	static protected $signals = true;

	static public function toggleSignals(bool $enabled)
	{
		self::$signals = $enabled;
	}

	static public function getPath(string $name): string
	{
		if (file_exists(PLUGINS_ROOT . '/' . $name . '.tar.gz')) {
			return 'phar://' . PLUGINS_ROOT . '/' . $name . '.tar.gz';
		}
		else {
			return PLUGINS_ROOT . '/' . $name;
		}
	}

	/**
	 * Déclenche le signal donné auprès des plugins enregistrés
	 * @param  string $signal Nom du signal
	 * @param  array  $params Paramètres du callback (array ou null)
	 * @return NULL 		  NULL si aucun plugin n'a été appelé,
	 * TRUE si un plugin a été appelé et a arrêté l'exécution,
	 * FALSE si des plugins ont été appelés mais aucun n'a stoppé l'exécution
	 */
	static public function fireSignal($signal, $params = null, &$callback_return = null)
	{
		if (!self::$signals) {
			return null;
		}

		// Process SYSTEM_SIGNALS first
		foreach (SYSTEM_SIGNALS as $system_signal) {
			if (key($system_signal) != $signal) {
				continue;
			}

			if (!is_callable(current($system_signal))) {
				throw new \LogicException(sprintf('System signal: cannot call "%s" for signal "%s"', current($system_signal), key($system_signal)));
			}

			if (true === call_user_func_array(current($system_signal), [&$params, &$callback_return])) {
				return true;
			}
		}

		$list = DB::getInstance()->get('SELECT * FROM plugins_signals WHERE signal = ?;', $signal);

		if (!count($list)) {
			return null;
		}

		if (null === $params) {
			$params = [];
		}

		foreach ($list as $row)
		{
			$path = self::getPath($row->plugin);

			// Ne pas appeler les plugins dont le code n'existe pas/plus,
			if (!$path)
			{
				continue;
			}

			$params['plugin_root'] = $path;

			$return = call_user_func_array('Garradin\\Plugin\\' . $row->callback, [&$params, &$callback_return]);

			if (true === $return) {
				return true;
			}
		}

		return false;
	}

	static public function listModulesAndPlugins(bool $installable = false): array
	{
		$list = [];

		if ($installable) {
			foreach (EM::getInstance(Module::class)->iterate('SELECT * FROM @TABLE WHERE enabled = 0;') as $m) {
				$list[$m->name] = ['module' => $m];
			}

			foreach (self::listInstallable() as $p) {
				$list[$p->name] = ['plugin'   => $p];
			}

			foreach (self::listInstalled() as $p) {
				if ($p->enabled) {
					continue;
				}

				$list[$p->name] = ['plugin'   => $p];
			}
		}
		else {
			foreach (EM::getInstance(Module::class)->iterate('SELECT * FROM @TABLE WHERE enabled = 1;') as $m) {
				$list[$m->name] = ['module' => $m];
			}

			foreach (self::listInstalled() as $p) {
				if (!$p->enabled) {
					continue;
				}

				$list[$p->name] = ['plugin'   => $p];
			}
		}

		foreach ($list as &$item) {
			$c = isset($item['plugin']) ? $item['plugin'] : $item['module'];
			$item['icon_url'] = $c->icon_url();
			$item['name'] = $c->name;
			$item['label'] = $c->label;
			$item['description'] = $c->description;
			$item['author'] = $c->author;
			$item['url'] = $c->url;
			$item['config_url'] = $c->hasConfig() ? $c->url($c::CONFIG_FILE) : null;
			$item['readme_url'] = $c->hasFile($c::README_FILE) ? $c->url($c::README_FILE) : null;
			$item['enabled'] = $c->enabled;
			$item['installed'] = isset($item['plugin']) ? $c->exists() : true;
			$item['restrict_section'] = $c->restrict_section;
			$item['restrict_level'] = $c->restrict_level;
		}

		unset($item);

		usort($list, fn ($a, $b) => strnatcasecmp($a['label'], $b['label']));

		return $list;
	}

	static public function listModulesAndPluginsMenu(Session $session): array
	{
		$list = [];

		foreach (DB::getInstance()->get('SELECT name, label, restrict_section, restrict_level FROM modules WHERE menu = 1;') as $m) {
			if ($m->restrict_section && !$session->canAccess($m->restrict_section, $m->restrict_level)) {
				continue;
			}

			$list['module_' . $m->name] = sprintf('<a href="%sm/%s/">%s</a>', ADMIN_URL, $m->name, $m->label);
		}

		foreach (DB::getInstance()->get('SELECT name, label, restrict_section, restrict_level FROM plugins WHERE menu = 1;') as $p) {
			if ($p->restrict_section && !$session->canAccess($p->restrict_section, $p->restrict_level)) {
				continue;
			}

			$list['plugin_' . $p->name] = sprintf('<a href="%sp/%s/">%s</a>', ADMIN_URL, $p->name, $p->label);
		}

		self::fireSignal('menu.item', compact('session'), $list);

		ksort($list);
		return $list;
	}

	static public function listModulesAndPluginsHomeButtons(Session $session): array
	{
		$list = [];

		foreach (DB::getInstance()->get('SELECT name, label, restrict_section, restrict_level FROM modules WHERE menu = 1;') as $m) {
			if ($m->restrict_section && !$session->canAccess($m->restrict_section, $m->restrict_level)) {
				continue;
			}

			$url = ADMIN_URL . 'm/' . $m->name . '/';
			$list[$m->name] = CommonFunctions::linkButton([
				'label' => $m->label,
				'icon' => $url . 'icon.svg',
				'href' => $url,
			]);
		}

		foreach (Modules::snippets(Modules::SNIPPET_HOME_BUTTON) as $name => $v) {
			$list[$name] = $v;
		}

		foreach (DB::getInstance()->get('SELECT name, label, restrict_section, restrict_level FROM plugins WHERE menu = 1;') as $p) {
			if ($p->restrict_section && !$session->canAccess($p->restrict_section, $p->restrict_level)) {
				continue;
			}

			$url = ADMIN_URL . 'p/' . $p->name . '/';
			$list[$p->name] = CommonFunctions::linkButton([
				'label' => $p->label,
				'icon' => $url . 'icon.svg',
				'href' => $url,
			]);
		}

		Plugins::fireSignal('home.button', ['user' => $session->getUser(), 'session' => $session], $list);

		ksort($list);
		return $list;
	}

	static public function get(string $name): ?Plugin
	{
		return EM::findOne(Plugin::class, 'SELECT * FROM @TABLE WHERE name = ?;', $name);
	}

	static public function listInstalled(): array
	{
		return EM::getInstance(Plugin::class)->all('SELECT * FROM @TABLE ORDER BY label COLLATE NOCASE ASC;');
	}

	/**
	 * Liste les plugins téléchargés mais non installés
	 */
	static public function listInstallable(): array
	{
		$list = [];
		$exists = DB::getInstance()->getAssoc('SELECT name, name FROM plugins;');

		foreach (glob(PLUGINS_ROOT . '/*') as $file)
		{
			if (substr($file, 0, 1) == '.') {
				continue;
			}

			if (is_dir($file) && file_exists($file . '/' . Plugin::META_FILE)) {
				$file = basename($file);
				$name = $file;
			}
			elseif (substr($file, -7) == '.tar.gz') {
				$file = basename($file);
				$name = substr($file, 0, -7);
			}
			else {
				continue;
			}

			// Ignore existing plugins
			if (in_array($name, $exists)) {
				continue;
			}

			$list[$file] = null;

			try {
				$p = new Plugin;
				$p->name = $name;
				$p->updateFromINI();
				$p->selfCheck();
				$list[$name] = $p;
			}
			catch (ValidationException $e) {
				$list[$name] = $file . ': ' . $e->getMessage();
			}
		}

		ksort($list);

		return $list;
	}

	static public function install(string $name): void
	{
		$plugin = self::get($name);

		if ($plugin) {
			$plugin->set('enabled', true);
			$plugin->save();
			return;
		}

		$p = new Plugin;
		$p->name = $name;

		if (!$p->hasFile($p::META_FILE)) {
			throw new UserException(sprintf('Le plugin "%s" n\'est pas une extension Paheko : fichier plugin.ini manquant.', $name));
		}

		$p->updateFromINI();

		$db = DB::getInstance();
		$db->begin();
		$p->set('enabled', true);
		$p->save();

		if ($p->hasFile($p::INSTALL_FILE)) {
			$p->call($p::INSTALL_FILE, true);
		}

		$db->commit();
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
			if (!$plugin->isAvailable()) {
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
