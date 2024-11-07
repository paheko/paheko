<?php

namespace Paheko;

use Paheko\Users\Session;

use Paheko\Accounting\Charts;

use KD2\HTTP;

use KD2\FossilInstaller;

class Upgrade
{
	const MIN_REQUIRED_VERSION = '1.3.0';

	static protected $installer = null;

	static public function preCheck(): bool
	{
		if (!file_exists(DB_FILE)) {
			return false;
		}

		$v = DB::getInstance()->version();

		if (version_compare($v, paheko_version(), '>='))
		{
			return false;
		}

		Install::checkAndCreateDirectories();

		if (!$v || version_compare($v, self::MIN_REQUIRED_VERSION, '<'))
		{
			throw new UserException(sprintf("Votre version de Paheko est trop ancienne pour être mise à jour. Mettez à jour vers Paheko %s avant de faire la mise à jour vers cette version.", self::MIN_REQUIRED_VERSION));
		}

		if (Static_Cache::exists('upgrade'))
		{
			$path = Static_Cache::getPath('upgrade');
			throw new UserException('Une mise à jour est déjà en cours.'
				. PHP_EOL . 'Si celle-ci a échouée et que vous voulez ré-essayer, supprimez le fichier suivant:'
				. PHP_EOL . $path);
		}

		return true;
	}

	static public function upgrade()
	{
		$db = DB::getInstance();
		$v = $db->version();

		Plugins::toggleSignals(false);

		Static_Cache::store('upgrade', 'Updating');

		// Créer une sauvegarde automatique
		$backup_file = sprintf(DATA_ROOT . '/association.pre_upgrade-%s.sqlite', paheko_version());
		Backup::make($backup_file);

		// Extend execution time, just in case
		if (false === strpos(@ini_get('disable_functions'), 'set_time_limit')) {
			@set_time_limit(600);
		}

		@ini_set('max_execution_time', 600);

		try {
			if (version_compare($v, '1.3.2', '<')) {
				$db->import(ROOT . '/include/migrations/1.3/1.3.2.sql');
			}

			if (version_compare($v, '1.3.3', '<')) {
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/migrations/1.3/1.3.3.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.3.5', '<')) {
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/migrations/1.3/1.3.5.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.3.7', '<')) {
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/migrations/1.3/1.3.7.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.3.8', '<')) {
				require ROOT . '/include/migrations/1.3/1.3.8.php';
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/migrations/1.3/1.3.8.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.3.9', '<')) {
				require ROOT . '/include/migrations/1.3/1.3.9.php';
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/migrations/1.3/1.3.9.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.3.10', '<')) {
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/migrations/1.3/1.3.10.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.3.11', '<')) {
				require ROOT . '/include/migrations/1.3/1.3.11.php';
			}

			if (version_compare($v, '1.3.12', '<')) {
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/migrations/1.3/1.3.12.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.3.13', '<')) {
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/migrations/1.3/1.3.13.sql');
				$db->commitSchemaUpdate();
			}

			Plugins::upgradeAllIfRequired();

			// Vérification de la cohérence des clés étrangères
			$db->foreignKeyCheck();

			// Delete local cached files
			Utils::resetCache(USER_TEMPLATES_CACHE_ROOT);
			Utils::resetCache(STATIC_CACHE_ROOT);

			// Make sure the shared cache is linked to the current version, and not
			// reset every time a single organization is upgraded
			$cache_version_file = SHARED_CACHE_ROOT . '/version';
			$cache_version = file_exists($cache_version_file) ? trim(file_get_contents($cache_version_file)) : null;

			// Only delete system cache when it's required
			if (paheko_version() !== $cache_version) {
				Utils::resetCache(SMARTYER_CACHE_ROOT);
				Utils::resetCache(SHARED_USER_TEMPLATES_CACHE_ROOT);
			}

			file_put_contents($cache_version_file, paheko_version());
			$db->setVersion(paheko_version());

			// reset last version check
			$db->exec('UPDATE config SET value = NULL WHERE key = \'last_version_check\';');

			Static_Cache::remove('upgrade');
		}
		catch (\Throwable $e)
		{
			if ($db->inTransaction()) {
				$db->rollback();
			}

			$db->close();
			rename($backup_file, DB_FILE);

			Static_Cache::remove('upgrade');

			if ($e instanceof UserException) {
				$e = new \RuntimeException($e->getMessage(), 0, $e);
			}

			throw $e;
		}

		Install::ping();

		$session = Session::getInstance();
		$session->logout();
	}

	static public function getLatestVersion(): ?\stdClass
	{
		if (!ENABLE_TECH_DETAILS && !ENABLE_UPGRADES) {
			return null;
		}

		$config = Config::getInstance();
		$last = $config->get('last_version_check');

		if ($last) {
			$last = json_decode($last);
		}

		// Only check once every two weeks
		if ($last && $last->time > (time() - 3600 * 24 * 5)) {
			return $last;
		}

		return null;
	}

	static public function fetchLatestVersion(): ?\stdClass
	{
		if (!ENABLE_TECH_DETAILS && !ENABLE_UPGRADES) {
			return null;
		}

		$config = Config::getInstance();
		$last = $config->get('last_version_check');

		if ($last) {
			$last = json_decode($last);
		}

		// Only check once every two weeks
		if ($last && $last->time > (time() - 3600 * 24 * 7)) {
			return $last;
		}

		$current_version = paheko_version();
		$last = (object) ['time' => time(), 'version' => null];
		$config->set('last_version_check', json_encode($last));
		$config->save();

		$last->version = self::getInstaller()->latest();

		if (version_compare($last->version, $current_version, '<=')) {
			$last->version = null;
		}

		$config->set('last_version_check', json_encode($last));
		$config->save();

		return $last;
	}

	static public function getInstaller(): FossilInstaller
	{
		if (!isset(self::$installer)) {
			$i = new FossilInstaller(WEBSITE, ROOT, CACHE_ROOT, '!^paheko-(.*)\.tar\.gz$!');
			$i->setPublicKeyFile(ROOT . '/pubkey.asc');

			if (0 === strpos(CACHE_ROOT, ROOT)) {
				$i->addIgnoredPath(substr(CACHE_ROOT, strlen(ROOT) + 1));
			}

			if (0 === strpos(DATA_ROOT, ROOT)) {
				$i->addIgnoredPath(substr(DATA_ROOT, strlen(ROOT) + 1));
			}

			if (0 === strpos(SHARED_CACHE_ROOT, ROOT)) {
				$i->addIgnoredPath(substr(SHARED_CACHE_ROOT, strlen(ROOT) + 1));
			}

			$i->addIgnoredPath('config.local.php');
			self::$installer = $i;
		}

		return self::$installer;
	}
}