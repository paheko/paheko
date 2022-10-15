<?php

namespace Garradin;

use Garradin\Users\Session;

use Garradin\Accounting\Charts;

use KD2\HTTP;

use KD2\FossilInstaller;

class Upgrade
{
	const MIN_REQUIRED_VERSION = '1.1.19';

	static protected $installer = null;

	static public function preCheck(): bool
	{
		if (!file_exists(DB_FILE)) {
			return false;
		}

		$v = DB::getInstance()->version();

		if (version_compare($v, garradin_version(), '>='))
		{
			return false;
		}

		Install::checkAndCreateDirectories();

		if (!$v || version_compare($v, self::MIN_REQUIRED_VERSION, '<'))
		{
			throw new UserException(sprintf("Votre version de Garradin est trop ancienne pour être mise à jour. Mettez à jour vers Garradin %s avant de faire la mise à jour vers cette version.", self::MIN_REQUIRED_VERSION));
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
		$backup = new Sauvegarde;
		$v = $db->version();

		Plugin::toggleSignals(false);

		Static_Cache::store('upgrade', 'Updating');

		// Créer une sauvegarde automatique
		$backup_file = sprintf(DATA_ROOT . '/association.pre_upgrade-%s.sqlite', garradin_version());
		$backup->make($backup_file);

		try {
			if (version_compare($v, '1.1.21', '<')) {
				$db->beginSchemaUpdate();
				// Add id_analytical column to services_fees
				$db->import(ROOT . '/include/data/1.1.21_migration.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.1.22', '<')) {
				$db->beginSchemaUpdate();
				// Create acc_accounts_balances view
				$db->import(ROOT . '/include/data/1.1.0_schema.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.1.23', '<')) {
				$db->begin();
				// Create acc_accounts_projects_balances view
				$db->import(ROOT . '/include/data/1.1.0_schema.sql');
				$db->commit();
			}

			if (version_compare($v, '1.1.24', '<')) {
				$db->begin();

				// Delete acc_accounts_projects_ebalances view
				$db->exec('DROP VIEW IF EXISTS acc_accounts_projects_balances;');

				$db->commit();
			}

			if (version_compare($v, '1.1.25', '<')) {
				$db->begin();

				// Just add email tables
				$db->import(ROOT . '/include/data/1.1.0_schema.sql');

				// Rename signals
				$db->import(ROOT . '/include/data/1.1.25_migration.sql');

				$db->commit();
			}

			if (version_compare($v, '1.1.27', '<')) {
				// Just add api_credentials tables
				$db->import(ROOT . '/include/data/1.1.0_schema.sql');
			}

			if (version_compare($v, '1.1.28', '<')) {
				$db->createFunction('html_decode', 'htmlspecialchars_decode');
				$db->exec('UPDATE files_search SET content = html_decode(content) WHERE content IS NOT NULL;');
			}

			if (version_compare($v, '1.1.29', '<')) {
				Charts::updateInstalled('fr_pca_2018');
				$db->import(ROOT . '/include/data/1.1.29_migration.sql');
			}

			if (version_compare($v, '1.1.30', '<')) {
				require ROOT . '/include/migrations/1.1/30.php';
			}

			if (version_compare($v, '1.2.0', '<')) {
				require ROOT . '/include/migrations/1.2/1.2.0.php';
			}

			Plugin::upgradeAllIfRequired();

			// Vérification de la cohérence des clés étrangères
			$db->foreignKeyCheck();

			// Delete local cached files
			Utils::resetCache(USER_TEMPLATES_CACHE_ROOT);
			Utils::resetCache(STATIC_CACHE_ROOT);

			$cache_version_file = SHARED_CACHE_ROOT . '/version';
			$cache_version = file_exists($cache_version_file) ? trim(file_get_contents($cache_version_file)) : null;

			// Only delete system cache when it's required
			if (garradin_version() !== $cache_version) {
				Utils::resetCache(SMARTYER_CACHE_ROOT);
			}

			file_put_contents($cache_version_file, garradin_version());
			$db->setVersion(garradin_version());

			// reset last version check
			$db->exec('UPDATE config SET value = NULL WHERE key = \'last_version_check\';');

			Static_Cache::remove('upgrade');
		}
		catch (\Exception $e)
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
		if ($last && $last->time > (time() - 3600 * 24 * 2)) {
			return $last;
		}

		$current_version = garradin_version();
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
			$i = new FossilInstaller(WEBSITE, ROOT, CACHE_ROOT, '!^garradin-(.*)\.tar\.gz$!');
			$i->setPublicKeyFile(ROOT . '/pubkey.asc');

			if (0 === ($pos = strpos(CACHE_ROOT, ROOT))) {
				$i->addIgnoredPath(substr(CACHE_ROOT, strlen(ROOT) + 1));
			}

			if (0 === ($pos = strpos(DATA_ROOT, ROOT))) {
				$i->addIgnoredPath(substr(DATA_ROOT, strlen(ROOT) + 1));
			}

			if (0 === ($pos = strpos(SHARED_CACHE_ROOT, ROOT))) {
				$i->addIgnoredPath(substr(SHARED_CACHE_ROOT, strlen(ROOT) + 1));
			}

			$i->addIgnoredPath('config.local.php');
			self::$installer = $i;
		}

		return self::$installer;
	}
}