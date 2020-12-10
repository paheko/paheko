<?php

namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Chart;
use Garradin\Entities\Accounting\Year;

/**
 * Pour procéder à l'installation de l'instance Garradin
 * Utile pour automatiser l'installation sans passer par la page d'installation
 */
class Install
{
	static public function reset(Membres\Session $session, $password, array $options = [])
	{
		$config = (object) Config::getInstance()->getConfig();
		$user = $session->getUser();

		if (!$session->checkPassword($password, $user->passe))
		{
			throw new UserException('Le mot de passe ne correspond pas.');
		}

		(new Sauvegarde)->create(date('Y-m-d-His-') . 'avant-remise-a-zero');

		DB::getInstance()->close();
		Config::deleteInstance();

		unlink(DB_FILE);

		// We can't use the real password, as it might not be valid (too short or compromised)
		$ok = self::install($config->nom_asso, $user->identite, $user->email, md5($password . SECRET_KEY));

		// Restore password
		DB::getInstance()->preparedQuery('UPDATE membres SET passe = ? WHERE id = 1;', [$session::hashPassword($password)]);

		if ($ok)
		{
			// Force l'installation de plugin système
			Plugin::checkAndInstallSystemPlugins();
		}

		return $ok;
	}

	static public function install($nom_asso, $nom_membre, $email_membre, $passe_membre)
	{
		$db = DB::getInstance(true);

		// Taille de la page de DB, on force à 4096 (défaut dans les dernières
		// versions de SQLite mais pas les vieilles)
		$db->exec('PRAGMA page_size = 4096;');
		$db->exec('VACUUM;');

		// Création de la base de données
		$db->begin();
		$db->exec('PRAGMA application_id = ' . DB::APPID . ';');
		$db->exec(file_get_contents(DB_SCHEMA));
		$db->commit();

		// Configuration de base
		// c'est dans Config::set que sont vérifiées les données utilisateur (renvoie UserException)
		$config = Config::getInstance();
		$config->set('nom_asso', $nom_asso);
		$config->set('email_asso', $email_membre);
		$config->set('site_asso', WWW_URL);
		$config->set('monnaie', '€');
		$config->set('pays', 'FR');
		$config->setVersion(garradin_version());

		$champs = Membres\Champs::importInstall();
		$champs->save(false); // Pas de copie car pas de table membres existante

		$config->set('champ_identifiant', 'email');
		$config->set('champ_identite', 'nom');

		// Création catégories
		$cats = new Membres\Categories;
		$id = $cats->add([
			'nom' => 'Membres actifs',
		]);
		$config->set('categorie_membres', $id);

		$id = $cats->add([
			'nom' => 'Anciens membres',
			'droit_inscription' => Membres::DROIT_AUCUN,
			'droit_wiki' => Membres::DROIT_AUCUN,
			'droit_membres' => Membres::DROIT_AUCUN,
			'droit_compta' => Membres::DROIT_AUCUN,
			'droit_config' => Membres::DROIT_AUCUN,
			'droit_connexion' => Membres::DROIT_AUCUN,
			'cacher' => 1,
		]);

		$id = $cats->add([
			'nom' => 'Administrateurs',
			'droit_inscription' => Membres::DROIT_AUCUN,
			'droit_wiki' => Membres::DROIT_ADMIN,
			'droit_membres' => Membres::DROIT_ADMIN,
			'droit_compta' => Membres::DROIT_ADMIN,
			'droit_config' => Membres::DROIT_ADMIN,
		]);

		// Création premier membre
		$membres = new Membres;
		$id_membre = $membres->add([
			'id_categorie'  =>  $id,
			'nom'           =>  $nom_membre,
			'email'         =>  $email_membre,
			'passe'         =>  $passe_membre,
			'pays'          =>  'FR',
		]);

		// Création wiki
		$page = Wiki::transformTitleToURI($nom_asso);
		$wiki = new Wiki;
		$id_page = $wiki->create([
			'titre' =>  $nom_asso,
			'uri'   =>  $page,
		]);

		$wiki->editRevision($id_page, 0, [
			'id_auteur' =>  $id_membre,
			'contenu'   =>  "Bienvenue dans le wiki de ".$nom_asso." !\n\nCliquez sur le bouton « éditer » pour modifier cette page.",
		]);

		// Création page wiki connexion
		$page = Wiki::transformTitleToURI('Bienvenue');
		$id_page = $wiki->create([
			'titre' =>  'Bienvenue',
			'uri'   =>  $page,
		]);
		$config->set('accueil_wiki', $page);

		$wiki->editRevision($id_page, 0, [
			'id_auteur' =>  $id_membre,
			'contenu'   =>  "Bienvenue dans l'administration de ".$nom_asso." !\n\n"
				.   "Utilisez le menu à gauche pour accéder aux différentes rubriques.",
		]);
		$config->set('accueil_connexion', $page);

        // Import plan comptable
        $chart = new Chart;
        $chart->label = 'Plan comptable associatif 2020 (Règlement ANC n°2018-06)';
        $chart->country = 'FR';
        $chart->code = 'PCA2018';
        $chart->save();
        $chart->accounts()->importCSV(ROOT . '/include/data/charts/fr_2018.csv');

        // Premier exercice
        $year = new Year;
        $year->label = sprintf('Exercice %d', date('Y'));
        $year->start_date = new \DateTime('January 1st');
        $year->end_date = new \DateTime('December 31');
        $year->id_chart = $chart->id();
        $year->save();

        // Compte bancaire
        $account = new Account;
        $account->import([
        	'label' => 'Compte courant',
        	'code' => '512A',
        	'type' => Account::TYPE_BANK,
        	'position' => Account::ASSET_OR_LIABILITY,
        	'id_chart' => $chart->id(),
        	'user' => 1,
        ]);
        $account->save();

		// Ajout d'une recherche avancée en exemple (membres)
		$query = (object) [
			'query' => [[
				'operator' => 'AND',
				'conditions' => [
					[
						'column'   => 'lettre_infos',
						'operator' => '= 1',
						'values'   => [],
					],
				],
			]],
			'order' => 'numero',
			'desc' => true,
			'limit' => '10000',
		];

		$recherche = new Recherche;
		$recherche->add('Membres inscrits à la lettre d\'information', null, $recherche::TYPE_JSON, 'membres', $query);

		// Ajout d'une recherche avancée en exemple (compta)
		$query = (object) [
			'query' => [[
				'operator' => 'AND',
				'conditions' => [
					[
						'column'   => 'a2.code',
						'operator' => 'IS NULL',
						'values'   => [],
					],
				],
			]],
			'order' => 't.id',
			'desc' => false,
			'limit' => '100',
		];

		$recherche = new Recherche;
		$recherche->add('Écritures sans projet', null, $recherche::TYPE_JSON, 'compta', $query);

		// Install welcome plugin if available
		$has_welcome_plugin = Plugin::getPath('welcome', false);

		if ($has_welcome_plugin) {
			Plugin::install('welcome', true);
		}

		return $config->save();
	}

	static public function checkAndCreateDirectories()
	{
		// Vérifier que les répertoires vides existent, sinon les créer
		$paths = [DATA_ROOT, PLUGINS_ROOT, CACHE_ROOT, CACHE_ROOT . '/static', CACHE_ROOT . '/compiled'];

		foreach ($paths as $path)
		{
			Utils::safe_mkdir($path);

			if (!is_dir($path))
			{
				throw new UserException('Le répertoire '.$path.' n\'existe pas ou n\'est pas un répertoire.');
			}

			// On en profite pour vérifier qu'on peut y lire et écrire
			if (!is_writable($path) || !is_readable($path))
			{
				throw new UserException('Le répertoire '.$path.' n\'est pas accessible en lecture/écriture.');
			}
		}

		return true;
	}

	static public function setLocalConfig($key, $value)
	{
		$path = ROOT . DIRECTORY_SEPARATOR . 'config.local.php';
		$new_line = sprintf('const %s = %s;', $key, var_export($value, true));

		if (file_exists($path))
		{
			$config = file_get_contents($path);

			$pattern = sprintf('/^.*(?:const\s+%s|define\s*\(.*%1$s).*$/m', $key);

			$config = preg_replace($pattern, $new_line, $config, -1, $count);

			if (!$count)
			{
				$config = preg_replace('/\?>.*/s', '', $config);
				$config .= PHP_EOL . $new_line . PHP_EOL;
			}
		}
		else
		{
			$config = '<?php' . PHP_EOL
				. 'namespace Garradin;' . PHP_EOL . PHP_EOL
				. $new_line . PHP_EOL;
		}

		return file_put_contents($path, $config);
	}
}