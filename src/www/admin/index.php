<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$cats = new Membres_Categories;
$categorie = $cats->get($user['id_categorie']);

$tpl->assign('categorie', $categorie);
$tpl->assign('verif_cotisation', Membres::checkCotisation($user['date_cotisation'], $categorie['duree_cotisation']));

$wiki = new Wiki;
$page = $wiki->getByURI($config->get('accueil_connexion'));
$tpl->assign('page', $page);

$tpl->display('admin/index.tpl');

// On réalise la sauvegarde auto à cet endroit, c'est un peu inefficace mais bon

if ($config->get('frequence_sauvegardes') && $config->get('nombre_sauvegardes'))
{
	$s = new Sauvegarde;
	$s->auto();
}

?>