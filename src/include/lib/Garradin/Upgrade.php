<?php

namespace Garradin;

use Garradin\Membres\Session;
use Garradin\Membres\Champs;

class Upgrade
{
	const MIN_REQUIRED_VERSION = '1.0.0';

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
		$user_is_logged = $session->isLogged(true);
		return true;
	}

	static public function upgrade()
	{
		$db = DB::getInstance();
		$v = $db->version();

		$session = Session::getInstance();
		$user_is_logged = $session->isLogged(true);

		Static_Cache::store('upgrade', 'Mise à jour en cours.');

		// Créer une sauvegarde automatique
		$backup_name = (new Sauvegarde)->create('pre-upgrade-' . garradin_version());

		try {
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

			if (version_compare($v, '1.1.0', '<='))
			{
				// Missing trigger
				$db->beginSchemaUpdate();

				$attachments = $db->getAssoc('SELECT id, nom FROM fichiers;');

				// Update Skriv content for attachments
				foreach ($db->iterate('SELECT rowid, contenu FROM wiki_revisions;') as $r) {
					$content = preg_replace_callback('!<<(image|fichier)\|(\d+)\|(gauche|droite|centre)>>!', function ($match) use ($attachments) {
						$name = $attachments[$match[2]] ?? '_ERREUR_fichier_inconnu_' . $match[2];
						$align = ($match[3] == 'centre' ? 'center' : ($match[3] == 'gauche' ? 'left' : 'right'));
						return sprintf('<<%s|%s|%s>>', $match[1] == 'fichier' ? 'file' : 'image', $name, $align);
					}, $r->contenu);

					$content = preg_replace_callback('!(image|fichier)://(\d+)!', function ($match) use ($attachments) {
						$name = $attachments[$match[2]] ?? '_ERREUR_fichier_inconnu_' . $match[2];
						return sprintf('#file:[%s]', $name);
					}, $content);

					if ($content != $r->contenu) {
						$db->update('wiki_revisions', ['contenu' => $content], 'rowid = :id', ['id' => $r->rowid]);
					}
				}

				$champs = new Champs($db->firstColumn('SELECT valeur FROM config WHERE cle = \'champs_membres\';'));
				$db->import(ROOT . '/include/data/1.1.0_migration.sql');

				// Rename membres table
				$champs->createTable($champs::TABLE  .'_tmp');

				$fields = $champs->getCopyFields();
				unset($fields['category_id']);
				$fields['id_categorie'] = 'category_id';
				$champs->copy($champs::TABLE, $champs::TABLE . '_tmp', $fields);

				$db->exec(sprintf('DROP TABLE IF EXISTS %s;', $champs::TABLE));
				$db->exec(sprintf('ALTER TABLE %s_tmp RENAME TO %1$s;', $champs::TABLE));

				$champs->createIndexes($champs::TABLE);

				$pages = $db->iterate('SELECT * FROM web_pages;');

				foreach ($pages as $data) {
					$page = new \Garradin\Entities\Web\Page;
					$page->load((array) $data);
					$page->syncSearch();
				}

				$db->commitSchemaUpdate();
			}

			// Vérification de la cohérence des clés étrangères
			$db->foreignKeyCheck();

			Utils::clearCaches();

			$db->setVersion(garradin_version());

			// reset last version check
			$db->exec('UPDATE config SET value = NULL WHERE key = \'last_version_check\';');

			Static_Cache::remove('upgrade');

			// Réinstaller les plugins système si nécessaire
			Plugin::checkAndInstallSystemPlugins();

			Plugin::upgradeAllIfRequired();
		}
		catch (\Exception $e)
		{
			$s = new Sauvegarde;
			$s->restoreFromLocal($backup_name);
			$s->remove($backup_name);
			Static_Cache::remove('upgrade');
			throw $e;
		}

		// Forcer à rafraîchir les données de la session si elle existe
		if ($user_is_logged)
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
}