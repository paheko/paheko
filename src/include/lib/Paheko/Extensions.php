<?php

namespace Paheko;

use Paheko\Entities\Extension;
use Paheko\Entities\Module;
use Paheko\Entities\Plugin;

use Paheko\Users\Session;
use Paheko\UserTemplate\Modules;
use Paheko\Plugins;
use Paheko\UserTemplate\CommonFunctions;

use KD2\DB\EntityManager as EM;

class Extensions
{
	static public function isAnyExtensionEnabled(): bool
	{
		return (bool) DB::getInstance()->firstColumn('
			SELECT 1 FROM modules WHERE enabled = 1 AND web = 0
			UNION ALL
			SELECT 1 FROM plugins WHERE enabled = 1 AND name != \'welcome\';');
	}

	static public function listAvailableButtons(): array
	{
		$list = self::listDisabled();

		// Sort items by label
		uasort($list, fn ($a, $b) => strnatcasecmp($a->label, $b->label));

		foreach ($list as &$item) {
			$url = sprintf('%s/%s/', $item->type == 'plugin' ? ADMIN_URL . 'p' : BASE_URL  . 'm', $item->name);
			$item = CommonFunctions::linkButton([
				'label' => $item->label,
				'icon' => $url . 'icon.svg',
				'href' => '!config/ext/?install=1&focus=' . $item->name,
			]);
		}

		return $list;
	}

	static public function get(string $name): ?Extension
	{
		$ext = Plugins::get($name);
		$ext ??= Plugins::getInstallable($name);
		$ext ??= Modules::get($name);

		if (null === $ext) {
			return null;
		}

		$e = new Extension;
		$e->normalize($ext);
		return $e;
	}

	static public function normalize($item): Extension
	{
		$e = new Extension;
		$e->normalize($item);
		return $e;
	}

	static public function toggle(string $name, bool $enabled)
	{
		$ext = self::get($name);
		$ext->toggle($enabled);
	}

	static protected function filterList(array &$list): void
	{
		foreach ($list as &$item) {
			$item = self::normalize($item);
		}

		unset($item);

		usort($list, fn ($a, $b) => strnatcasecmp($a->label ?? $a->name, $b->label ?? $b->name));

		array_walk($list, fn(&$a) => $a = (object) $a);
	}

	static public function listDisabled(): array
	{
		$list = [];

		foreach (EM::getInstance(Module::class)->iterate('SELECT * FROM @TABLE WHERE enabled = 0;') as $m) {
			$list[$m->name] = $m;
		}

		foreach (Plugins::listInstallable() as $name => $p) {
			$list[$name] = $p;
		}

		foreach (Plugins::listInstalled() as $p) {
			if ($p->enabled) {
				continue;
			}

			$list[$p->name] = $p;
		}

		self::filterList($list);
		return $list;
	}

	static public function listEnabled(): array
	{
		$list = [];

		foreach (EM::getInstance(Module::class)->iterate('SELECT * FROM @TABLE WHERE enabled = 1;') as $m) {
			$list[$m->name] = $m;
		}

		foreach (Plugins::listInstalled() as $p) {
			if (!$p->enabled) {
				continue;
			}

			if (!$p->hasCode()) {
				$p->set('enabled', false);
				$p->save(false);
				continue;
			}

			$list[$p->name] = $p;
		}

		self::filterList($list);
		return $list;
	}

	static public function listMenu(Session $session): array
	{
		$list = [];

		$sql = 'SELECT \'module\' AS type, name, label, restrict_section, restrict_level FROM modules WHERE menu = 1 AND enabled = 1
			UNION ALL
			SELECT \'plugin\' AS type, name, label, restrict_section, restrict_level FROM plugins WHERE menu = 1 AND enabled = 1;';

		foreach (DB::getInstance()->get($sql) as $item) {
			if ($item->restrict_section && !$session->canAccess($item->restrict_section, $item->restrict_level)) {
				continue;
			}

			$list[$item->type . '_' . $item->name] = $item;
		}

		// Sort items by label
		uasort($list, fn ($a, $b) => strnatcasecmp($a->label, $b->label));

		foreach ($list as &$item) {
			$item = sprintf('<a href="%s/%s/">%s</a>',
				$item->type == 'plugin' ? ADMIN_URL . 'p' : BASE_URL  . 'm',
				$item->name,
				$item->label
			);
		}

		unset($item);

		// Append plugins from signals
		$signal = Plugins::fire('menu.item', false, compact('session'), $list);

		return $signal ? $signal->getOut() : $list;
	}

	static public function listHomeButtons(Session $session): array
	{
		$list = [];

		$sql = 'SELECT \'module\' AS type, name, label, restrict_section, restrict_level FROM modules WHERE home_button = 1 AND enabled = 1
			UNION ALL
			SELECT \'plugin\' AS type, name, label, restrict_section, restrict_level FROM plugins WHERE home_button = 1 AND enabled = 1;';

		foreach (DB::getInstance()->get($sql) as $item) {
			if ($item->restrict_section && !$session->canAccess($item->restrict_section, $item->restrict_level)) {
				continue;
			}

			$list[$item->type . '_' . $item->name] = $item;
		}

		// Sort items by label
		uasort($list, fn ($a, $b) => strnatcasecmp($a->label, $b->label));

		foreach ($list as &$item) {
			$url = sprintf('%s/%s/', $item->type == 'plugin' ? ADMIN_URL . 'p' : BASE_URL  . 'm', $item->name);
			$item = CommonFunctions::linkButton([
				'label' => $item->label,
				'icon' => $url . 'icon.svg',
				'href' => $url,
			]);
		}

		unset($item);

		foreach (Modules::snippets(Modules::SNIPPET_HOME_BUTTON) as $name => $v) {
			$list['module_' . $name] = $v;
		}

		$signal = Plugins::fire('home.button', false, ['user' => $session->getUser(), 'session' => $session], $list);

		return $signal ? $signal->getOut() : $list;
	}

}
