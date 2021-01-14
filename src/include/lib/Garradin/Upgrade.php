<?php

namespace Garradin;

use Garradin\Membres\Session;

class Upgrade
{
	const MIN_REQUIRED_VERSION = '1.0.0-rc8';

	static public function preCheck(): bool
	{
		$v = DB::getInstance()->firstColumn('SELECT valeur FROM config WHERE cle = \'version\';');

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
		$v = DB::getInstance()->firstColumn('SELECT valeur FROM config WHERE cle = \'version\';');

		$session = Session::getInstance();
		$user_is_logged = $session->isLogged(true);

		Static_Cache::store('upgrade', 'Mise à jour en cours.');

		$db = DB::getInstance();

		// reset last version check
		$db->exec('UPDATE config SET valeur = NULL WHERE cle = \'last_version_check\';');

		// Créer une sauvegarde automatique
		$backup_name = (new Sauvegarde)->create('pre-upgrade-' . garradin_version());

		try {
			if (version_compare($v, '1.1.0', '<='))
			{
				// Missing trigger
				$db->beginSchemaUpdate();
				$db->createFunction('sha1', 'sha1');
				$db->import(ROOT . '/include/data/1.1.0_migration.sql');
				$db->commitSchemaUpdate();
			}

			// Vérification de la cohérence des clés étrangères
			$db->foreignKeyCheck();

			Utils::clearCaches();

			DB::getInstance()->update('config', ['valeur' => garradin_version()], 'cle = \'version\';');

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
}