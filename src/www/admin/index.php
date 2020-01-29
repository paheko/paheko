<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$cats = new Membres\Categories;
$categorie = $cats->get($user->id_categorie);

$tpl->assign('categorie', $categorie);

$wiki = new Wiki;
$page = $wiki->getByURI($config->get('accueil_connexion'));
$tpl->assign('page', $page);

$tpl->assign('custom_css', ['wiki.css']);

$tpl->assign('banniere', Plugin::fireSignal('accueil.banniere', ['user' => $user, 'session' => $session]));

$tpl->display('admin/index.tpl');
flush();

// Si pas de cron on réalise les tâches automatisées à ce moment-là
// c'est pas idéal mais mieux que rien
if (!USE_CRON)
{
	require_once ROOT . '/cron.php';
}
