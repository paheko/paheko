<?php

namespace Garradin;

/**
 * Pour procéder à l'installation de l'instance Garradin
 * Utile pour automatiser l'installation sans passer par la page d'installation
 */
class Install
{
	static public function install($nom_asso, $adresse_asso, $email_asso, $nom_categorie, $nom_membre, $email_membre, $passe_membre, $site_asso = WWW_URL)
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
		$config->set('adresse_asso', $adresse_asso);
		$config->set('email_asso', $email_asso);
		$config->set('site_asso', WWW_URL);
		$config->set('monnaie', '€');
		$config->set('pays', 'FR');
		$config->set('email_envoi_automatique', $email_asso);
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
			'nom' => ucfirst($nom_categorie),
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
		$config->set('accueil_wiki', $page);
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
		$config->set('accueil_connexion', $page);
		$id_page = $wiki->create([
			'titre' =>  'Bienvenue',
			'uri'   =>  $page,
		]);

		$wiki->editRevision($id_page, 0, [
			'id_auteur' =>  $id_membre,
			'contenu'   =>  "Bienvenue dans l'administration de ".$nom_asso." !\n\n"
				.   "Utilisez le menu à gauche pour accéder aux différentes rubriques.",
		]);

		// Mise en place compta
		$comptes = new Compta\Comptes;
		$comptes->importPlan();

		$comptes = new Compta\Categories;
		$comptes->importCategories();

		$ex = new Compta\Exercices;
		$ex->add([
			'libelle'   =>  'Premier exercice',
			'debut'     =>  date('Y-01-01'),
			'fin'       =>  date('Y-12-31')
		]);

		return $config->save();
	}

	static public function checkAndCreateDirectories()
	{
		// Vérifier que les répertoires vides existent, sinon les créer
		$paths = [DATA_ROOT, PLUGINS_ROOT, CACHE_ROOT, CACHE_ROOT . '/static', CACHE_ROOT . '/compiled'];

		foreach ($paths as $path)
		{
		    if (!file_exists($path))
		    {
		        mkdir($path);
		    }

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