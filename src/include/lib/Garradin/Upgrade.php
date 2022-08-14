<?php

namespace Garradin;

use Garradin\Users\Session;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

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

		if (!$v || version_compare($v, self::MIN_REQUIRED_VERSION, '<'))
		{
			throw new UserException(sprintf("Votre version de Garradin est trop ancienne pour être mise à jour. Mettez à jour vers Garradin %s avant de faire la mise à jour vers cette version.", self::MIN_REQUIRED_VERSION));
		}

		Install::checkAndCreateDirectories();

		if (Static_Cache::exists('upgrade'))
		{
			$path = Static_Cache::getPath('upgrade');
			throw new UserException('Une mise à jour est déjà en cours.'
				. PHP_EOL . 'Si celle-ci a échouée et que vous voulez ré-essayer, supprimez le fichier suivant:'
				. PHP_EOL . $path);
		}

		// Voir si l'utilisateur est loggé, on le fait ici pour le cas où
		// il y aurait déjà eu des entêtes envoyés au navigateur plus bas
		$session = Session::getInstance();
		$session->start(true);
		$session->isLogged(true);
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

			if (version_compare($v, '1.2.0', '<')) {
				$db->beginSchemaUpdate();

				// Get old keys
				$config = (object) $db->getAssoc('SELECT key, value FROM config WHERE key IN (\'champs_membres\', \'champ_identifiant\', \'champ_identite\');');

				// Create config_users_fields table, and lots of stuff
				$db->import(ROOT . '/include/data/1.2.0_schema.sql');

				// Migrate users table
				$df = \Garradin\Users\DynamicFields::fromOldINI($config->champs_membres, $config->champ_identifiant, $config->champ_identite, 'numero');
				$df->save(false);

				// Migrate other stuff
				$db->import(ROOT . '/include/data/1.2.0_migration.sql');

				// Update searches
				foreach ($db->iterate('SELECT * FROM searches;') as $row) {
					if ($row->type == 'json') {
						$json = json_decode($row->content);

						if (!$json) {
							$db->delete('searches', 'id = ?', $row->id);
							continue;
						}

						$json->groups = $json->query;
						unset($json->query, $json->limit);

						$content = json_encode($json);
					}
					else {
						$content = preg_replace('/\bmembres\b/', 'users', $row->content);
					}

					$db->update('searches', ['content' => $content], 'id = ' . (int) $row->id);
				}

				// Add signature to files
				$files = $db->firstColumn('SELECT value FROM config WHERE key = \'files\';');
				$files = json_decode($files);
				$files->signature = null;
				$db->exec(sprintf('REPLACE INTO config (key, value) VALUES (\'files\', %s);', $db->quote(json_encode($files))));

				// Move skeletons from skel/ to skel/web/
				// Don't use Files::get to get around validatePath security
				$file = Files::callStorage('get', File::CONTEXT_SKELETON);

				if ($file) {
					$file->rename(File::CONTEXT_SKELETON . '/web');

					// Prepend "./" to includes functions file parameter
					foreach (Files::list(File::CONTEXT_SKELETON . '/web') as $file) {
						if ($file->type != File::TYPE_FILE || !preg_match('/\.(?:txt|css|js|html|htm)$/', $file->name)) {
							continue;
						}

						$file->setContent(preg_replace('/(\s+file=")(\w+)/', '$1./$2', $file->fetch()));
					}
				}

				$db->commitSchemaUpdate();
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

			Plugin::upgradeAllIfRequired();
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

		$session = Session::getInstance();
		$user_is_logged = $session->isLogged(true);

		// Forcer à rafraîchir les données de la session si elle existe
		if ($user_is_logged && !headers_sent())
		{
			$session->refresh();
		}
	}

	/**
	 * Move data from root to data/ subdirectory
	 * (migration from 1.0 to 1.1 version)
	 */
	static public function moveDataRoot(): void
	{
		Utils::safe_mkdir(ROOT . '/data');
		file_put_contents(ROOT . '/data/index.html', '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body></html>');

		rename(ROOT . '/cache', ROOT . '/data/cache');
		rename(ROOT . '/plugins', ROOT . '/data/plugins');

		$files = glob(ROOT . '/*.sqlite');

		foreach ($files as $file) {
			rename($file, ROOT . '/data/' . basename($file));
		}
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