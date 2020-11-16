<?php

namespace Garradin;

use Garradin\Membres\Session;

class Upgrade
{
	static public function preCheck()
	{
		$config = Config::getInstance();
		$v = $config->getVersion();

		if (version_compare($v, garradin_version(), '>='))
		{
			throw new UserException("Pas de mise à jour à faire.");
		}

		if (!$v || version_compare($v, '0.9.8', '<'))
		{
			throw new UserException("Votre version de Garradin est trop ancienne pour être mise à jour. Mettez à jour vers Garradin 0.9.8 avant de faire la mise à jour vers cette version.");
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
		$session = new Session;
		$user_is_logged = $session->isLogged(true);
	}

	static public function upgrade()
	{
		$config = Config::getInstance();
		$v = $config->getVersion();

		$session = new Session;
		$user_is_logged = $session->isLogged(true);

		Static_Cache::store('upgrade', 'Mise à jour en cours.');

		$db = DB::getInstance();

		// Créer une sauvegarde automatique
		$backup_name = (new Sauvegarde)->create('pre-upgrade-' . garradin_version());

		try {
			if (version_compare($v, '1.0.0-alpha1', '<'))
			{
				$db->beginSchemaUpdate();
				$db->import(ROOT . '/include/data/1.0.0_migration.sql');
				$db->commitSchemaUpdate();

				// Import nouveau plan comptable
				$chart = new \Garradin\Entities\Accounting\Chart;
				$chart->label = 'Plan comptable associatif 2018';
				$chart->country = 'FR';
				$chart->code = 'PCA2018';
				$chart->save();
				$chart->accounts()->importCSV(ROOT . '/include/data/charts/fr_2018.csv');
			}

			// Vérification de la cohérence des clés étrangères
			$db->foreignKeyCheck();

			Utils::clearCaches();

			$config->setVersion(garradin_version());

			Static_Cache::remove('upgrade');

			// Réinstaller les plugins système si nécessaire
			Plugin::checkAndInstallSystemPlugins();

			// Mettre à jour les plugins si nécessaire
			foreach (Plugin::listInstalled() as $id=>$infos)
			{
				// Ne pas tenir compte des plugins dont le code n'est pas dispo
				if ($infos->disabled)
				{
					continue;
				}

				$plugin = new Plugin($id);

				if ($plugin->needUpgrade())
				{
					$plugin->upgrade();
				}

				unset($plugin);
			}
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