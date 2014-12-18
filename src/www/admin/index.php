<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$cats = new Membres\Categories;
$categorie = $cats->get($user['id_categorie']);

$tpl->assign('categorie', $categorie);

$wiki = new Wiki;
$page = $wiki->getByURI($config->get('accueil_connexion'));
$tpl->assign('page', $page);

$cats = new Membres\Categories;

$categorie = $cats->get($user['id_categorie']);

$cotisations = new Membres\Cotisations;

if (!empty($categorie['id_cotisation_obligatoire']))
{
	$tpl->assign('cotisation', $cotisations->isMemberUpToDate($user['id'], $categorie['id_cotisation_obligatoire']));
}
else
{
	$tpl->assign('cotisation', false);
}

$tpl->display('admin/index.tpl');
flush();

// Si pas de cron on réalise les tâches automatisées à ce moment-là
// c'est pas idéal mais mieux que rien
if (!USE_CRON)
{
	require_once ROOT . '/cron.php';
}

?>