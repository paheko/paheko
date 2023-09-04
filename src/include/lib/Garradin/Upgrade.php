<?php

namespace Garradin;

use Garradin\Membres\Session;
use Garradin\Membres\Champs;

use Garradin\Accounting\Charts;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

use KD2\HTTP;

use KD2\FossilInstaller;

class Upgrade
{
	const MIN_REQUIRED_VERSION = '0.9.8';

	static protected $installer = null;

	static public function preCheck(): bool
	{
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
		$v = $db->version();

		Static_Cache::store('upgrade', 'Mise à jour en cours.');

		// Créer une sauvegarde automatique
		$backup_name = (new Sauvegarde)->create(false, 'pre-upgrade-' . garradin_version());

		try {
			if (version_compare($v, '1.0.0-rc1', '<'))
			{
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/data/1.0.0_migration.sql');
				$db->commitSchemaUpdate();
			}


			if (version_compare($v, '1.0.0-rc10', '<'))
			{
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/data/1.0.0-rc10_migration.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.0.0-beta1', '>=') && version_compare($v, '1.0.0-rc11', '<'))
			{
				// Missing trigger
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/data/1.0.0_schema.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.0.0-rc14', '<'))
			{
				// Missing trigger
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/data/1.0.0-rc14_migration.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.0.0-rc16', '<'))
			{
				// Missing trigger
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/data/1.0.0-rc16_migration.sql');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.0.1', '<'))
			{
				// Missing trigger
				$db->begin();
				$db->import(ROOT . '/include/data/1.0.1_migration.sql');
				$db->commit();
			}

			if (version_compare($v, '1.0.3', '<'))
			{
				// Missing trigger
				$db->begin();
				$db->import(ROOT . '/include/data/1.0.3_migration.sql');
				$db->commit();
			}

			if (version_compare($v, '1.0.6', '<'))
			{
				// Missing trigger
				$db->begin();
				$db->import(ROOT . '/include/data/1.0.6_migration.sql');
				$db->commit();
			}

			if (version_compare($v, '1.0.7', '<'))
			{
				// Missing trigger
				$db->begin();
				$db->import(ROOT . '/include/data/1.0.7_migration.sql');
				$db->commit();
			}

			if (version_compare($v, '1.1.0-beta1', '<'))
			{
				// Missing trigger
				$db->beginSchemaUpdate();

				$attachments = $db->getAssoc('SELECT f.id, w.uri || \'/\' || f.id || \'_\' || f.nom FROM fichiers f
					INNER JOIN fichiers_wiki_pages fw ON fw.fichier = f.id
					INNER JOIN wiki_pages w ON w.id = fw.id;');

				// Update Skriv content for attachments
				foreach ($db->iterate('SELECT r.rowid, r.contenu, p.uri FROM wiki_revisions r INNER JOIN wiki_pages p ON p.revision = r.revision AND p.id = r.id_page;') as $r) {
					$uri = $r->uri;
					$content = preg_replace_callback('!<<(image|fichier)\s*\|\s*(\d+)\s*(?:\|\s*(gauche|droite|centre))?\s*(?:\|\s*(.+)\s*)?>>!', function ($match) use ($attachments, $uri) {
						if (isset($attachments[$match[2]])) {
							$name = $attachments[$match[2]];

							if (dirname($name) == $uri) {
								$name = basename($name);
							}
							else {
								$name = '../' . $name;
							}
						}
						else {
							$name = '_ERREUR_fichier_inconnu_' . $match[2];
						}

						if (isset($match[3])) {
							$align = '|' . ($match[3] == 'centre' ? 'center' : ($match[3] == 'gauche' ? 'left' : 'right'));
						}
						else {
							$align = '';
						}

						$caption = isset($match[4]) ? '|' . $match[4] : '';

						return sprintf('<<%s|%s%s%s>>', $match[1] == 'fichier' ? 'file' : 'image', $name, $align, $caption);
					}, $r->contenu);

					$content = preg_replace_callback('!(image|fichier)://(\d+)!', function ($match) use ($attachments) {
						$name = $attachments[$match[2]] ?? '_ERREUR_fichier_inconnu_' . $match[2];
						return sprintf('#file:[%s]', $name);
					}, $content);

					if ($content != $r->contenu) {
						$db->update('wiki_revisions', ['contenu' => $content], 'rowid = :id', ['id' => $r->rowid]);
					}
				}

				$id_field = $db->firstColumn('SELECT valeur FROM config WHERE cle = \'champ_identifiant\';');
				$champs = new Champs($db->firstColumn('SELECT valeur FROM config WHERE cle = \'champs_membres\';'));
				$db->import(ROOT . '/include/data/1.1.0_migration.sql');

				// Rename membres table
				$champs->createTable($champs::TABLE  .'_tmp');

				$fields = $champs->getCopyFields(true);
				unset($fields['id_category']);
				$fields['id_categorie'] = 'id_category';
				$champs->copy($champs::TABLE, $champs::TABLE . '_tmp', $fields);

				$db->exec(sprintf('DROP TABLE IF EXISTS %s;', $champs::TABLE));
				$db->exec(sprintf('ALTER TABLE %s_tmp RENAME TO %1$s;', $champs::TABLE));

				$champs->createIndexes($champs::TABLE, $id_field);

				$db->commitSchemaUpdate();

				// Migrate to a different storage
				if (FILE_STORAGE_BACKEND != 'SQLite') {
					Files::migrateStorage('SQLite', FILE_STORAGE_BACKEND, null, FILE_STORAGE_CONFIG);
					Files::truncateStorage('SQLite', null);
				}

				$pages = $db->iterate('SELECT * FROM web_pages;');

				foreach ($pages as $data) {
					$page = new \Garradin\Entities\Web\Page;
					$page->exists(true);
					$page->load((array) $data);
					$page->syncSearch();
				}
			}

			if (version_compare($v, '1.1.1', '<')) {
				// Reset admin_background if the file does not exist
				$bg = $db->firstColumn('SELECT value FROM config WHERE key = \'admin_background\';');

				if ($bg) {
					$file = Files::get($bg);

					if (!$file) {
						$db->exec('UPDATE config SET value = NULL WHERE key = \'admin_background\';');
					}
				}

				// Fix links of admin homepage
				$homepage = $db->firstColumn('SELECT value FROM config WHERE key = \'admin_homepage\';');

				if ($homepage) {
					$file = Files::get($homepage);

					if ($file) {
						$content = $file->fetch();
						$new_content = preg_replace_callback(';\[\[((?!\]\]).*)\]\];', function ($match) {
							$link = explode('|', $match[1]);
							if (count($link) == 2) {
								list($label, $link) = $link;
							}
							else {
								$label = $link = $link[0];
							}

							if (strpos(trim($link), '/') !== false) {
								return $match[0];
							}

							$link = sprintf('!web/page.php?p=%s', trim($link));
							return sprintf('[[%s|%s]]', $label, $link);
						}, $content);

						if ($new_content != $content) {
							Files::disableQuota();
							$file->setContent($new_content);
						}
					}
				}
			}

			if (version_compare($v, '1.1.3', '<')) {
				// Missing trigger
				$db->begin();
				$db->import(ROOT . '/include/data/1.1.3_migration.sql');
				$db->commit();
			}

			if (version_compare($v, '1.1.4', '<')) {
				// Set config file names
				$file = Files::get(Config::FILES['admin_background']);
				$db->update('config', ['value' => $file ? Config::FILES['admin_background'] : null], 'key = :key', ['key' => 'admin_background']);

				$file = Files::get(Config::FILES['admin_homepage']);
				$db->update('config', ['value' => $file ? Config::FILES['admin_homepage'] : null], 'key = :key', ['key' => 'admin_homepage']);
			}

			if (version_compare($v, '1.1.7', '<')) {
				$db->begin();
				$db->import(ROOT . '/include/data/1.1.7_migration.sql');
				$db->commit();
			}

			if (version_compare($v, '1.1.8', '<')) {
				$db->begin();
				// Force sync to remove pages that don't exist anymore
				\Garradin\Web\Web::sync();

				$uris = [];
				$i = 1;

				$treat_duplicate_uris = function ($path) use (&$i, &$uris, &$treat_duplicate_uris) {
					// Rename duplicate URIs
					foreach (Files::callStorage('list', $path) as $f) {
						if ($f->type != $f::TYPE_DIRECTORY) {
							continue;
						}

						if (array_key_exists($f->name, $uris)) {
							$f->changeFileName($f->name . '_' . $i++);
						}

						$uris[$f->name] = $f->path;

						$treat_duplicate_uris($f->path);
					}
				};

				$treat_duplicate_uris(\Garradin\Entities\Files\File::CONTEXT_WEB);

				// Force sync to add renamed pages
				\Garradin\Web\Web::sync();

				// Add UNIQUE index
				$db->import(ROOT . '/include/data/1.1.8_migration.sql');

				$db->commit();
			}

			if (version_compare($v, '1.1.8', '==')) {
				// Force sync to add missing pages if you had the buggy 1.1.8 version
				\Garradin\Web\Web::sync(true);
			}

			if (version_compare($v, '1.1.10', '<')) {
				\Garradin\Web\Web::sync(); // Force sync of web pages
				Files::syncVirtualTable('', true);

				$db->begin();
				$db->exec(sprintf('DELETE FROM files_search WHERE path NOT IN (SELECT path FROM %s);', Files::getVirtualTableName()));
				$db->commit();
			}

			if (version_compare($v, '1.1.15', '<')) {
				$db->begin();
				$db->import(ROOT . '/include/data/1.1.15_migration.sql');
				$db->commit();
			}

			if (version_compare($v, '1.1.16', '<')) {
				$files = Config::FILES;

				foreach ($files as $key => &$set) {
					$f = Files::get($set);
					$set = $f !== null ? $f->modified->getTimestamp() : null;
				}

				unset($set);

				// Migrate files
				if ($f = Files::get(File::CONTEXT_SKELETON . '/favicon.png')) {
					$f->copy(Config::FILES['favicon']);
					$files['favicon'] = $f->modified->getTimestamp();
				}

				if ($f = Files::get(File::CONTEXT_SKELETON . '/logo.png')) {
					$f->copy(Config::FILES['icon']);
					$files['icon'] = $f->modified->getTimestamp();
				}

				$db->begin();
				$db->exec('DELETE FROM config WHERE key IN (\'admin_background\', \'admin_css\', \'admin_homepage\');');
				$db->exec(sprintf('INSERT INTO config (key, value) VALUES (\'files\', %s);', $db->quote(json_encode($files))));
				$db->commit();
			}

			if (version_compare($v, '1.1.18', '<')) {
				$db->begin();
				// Re-do the 1.1.15 migration as the LIKE did not work and accounts were not updated
				$db->import(ROOT . '/include/data/1.1.15_migration.sql');
				$db->commit();
			}

			if (version_compare($v, '1.1.19', '<')) {
				$db->exec('VACUUM;'); // This will rebuild the index correctly, fixing the corrupted DB

				// Some people were able to insert invalid charsets in the database, this messes up the indexes
				// Let's try to fix that
				$db->createFunction('utf8_encode', [Utils::class, 'utf8_encode']);
				$db->beginSchemaUpdate();

				// Now let's fix the content itself
				$res = $db->first('SELECT * FROM membres WHERE 1;');

				$columns = array_keys((array) $res);
				$columns = array_map(fn($c) => sprintf('"%s" = utf8_encode("%1$s")', $c), $columns);
				$db->exec(sprintf('UPDATE membres SET %s;', implode(', ', $columns)));

				// Let's re-create users table with the correct index
				$champs = Config::getInstance()->champs_membres;
				$db->exec('ALTER TABLE membres RENAME TO membres_old;');
				$db->commit();
				$db->close();
				$db->connect();
				$db->beginSchemaUpdate();
				$champs->create('membres');
				$champs->copy('membres_old', 'membres');
				$db->exec('DROP TABLE membres_old;');

				// Set new types for accounts
				$db->import(ROOT . '/include/data/1.1.19_migration.sql');

				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.1.21', '<')) {
				$db->begin();
				// Add id_analytical column to services_fees
				$db->import(ROOT . '/include/data/1.1.21_migration.sql');
				$db->commit();
			}

			if (version_compare($v, '1.1.22', '<')) {
				$db->begin();
				// Create acc_accounts_balances view
				$db->import(ROOT . '/include/data/1.1.0_schema.sql');
				$db->commit();
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
				$db->import(ROOT . '/include/data/1.1.29_migration.sql');
			}

			if (version_compare($v, '1.1.30', '<')) {
				require ROOT . '/include/migrations/1.1/30.php';
			}

			if (version_compare($v, '1.1.31', '<')) {
				$db->import(ROOT . '/include/migrations/1.1/31.sql');
			}

			if (version_compare($v, '1.2.0', '<')) {
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/migrations/1.2/1.2.0.sql');
				Charts::updateInstalled('fr_pca_2018');
				Charts::updateInstalled('fr_pca_1999');
				Charts::updateInstalled('fr_pcc_2020');
				Charts::updateInstalled('fr_pcg_2014');
				Charts::updateInstalled('be_pcmn_2019');
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.2.1', '<')) {
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/migrations/1.2/1.2.1.sql');
				Charts::resetRules(['FR', 'CH', 'BE']);
				$db->commitSchemaUpdate();
			}

			if (version_compare($v, '1.2.2', '<')) {
				require ROOT . '/include/migrations/1.2/1.2.2.php';
			}

			if (version_compare($v, '1.2.7', '<')) {
				require ROOT . '/include/migrations/1.2/1.2.7.php';
			}

			if (version_compare($v, '1.2.11', '<')) {
				// This will rebuild the index after the collation change in the 1.2.10 release
				$db->exec('VACUUM;');
			}

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

			try {
				$s = new Sauvegarde;
				$s->restoreFromLocal($backup_name);
				$s->remove($backup_name);
			}
			catch (\Exception $e2) {
				throw $e;
			}

			Static_Cache::remove('upgrade');
			throw $e;
		}

		Install::ping();

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
		if ($last && $last->time > (time() - 3600 * 24 * 7)) {
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
			$i = new FossilInstaller(WEBSITE, ROOT, CACHE_ROOT, '!^paheko-(.*)\.tar\.gz$!');
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