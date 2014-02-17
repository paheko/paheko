<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$cats = new Membres_Categories;
$categorie = $cats->get($user['id_categorie']);

$tpl->assign('categorie', $categorie);

$wiki = new Wiki;
$page = $wiki->getByURI($config->get('accueil_connexion'));
$tpl->assign('page', $page);

$cats = new Membres_Categories;

$categorie = $cats->get($user['id_categorie']);

$cotisations = new Cotisations_Membres;

if (!empty($categorie['id_cotisation_obligatoire']))
{
	$tpl->assign('cotisation', $cotisations->isMemberUpToDate($user['id'], $categorie['id_cotisation_obligatoire']));
}
else
{
	$tpl->assign('cotisation', false);
}

$tpl->display('admin/index.tpl');

// On réalise la sauvegarde auto à cet endroit, c'est un peu inefficace mais bon

if ($config->get('frequence_sauvegardes') && $config->get('nombre_sauvegardes'))
{
	$s = new Sauvegarde;
	$s->auto();
}

?>