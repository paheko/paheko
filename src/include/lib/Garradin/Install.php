<?php

namespace Garradin;

class Install
{
	static public function install($nom_asso, $adresse_asso, $email_asso, $nom_categorie, $nom_membre, $email_membre, $passe_membre, $site_asso = WWW_URL)
	{
		$db = DB::getInstance(true);

		// Création de la base de données
		$db->exec('BEGIN;');
		$db->exec(file_get_contents(DB_SCHEMA));
		$db->exec('END;');

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
}