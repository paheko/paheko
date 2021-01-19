<?php

namespace Garradin;

use Garradin\Files\Files;

require_once __DIR__ . '/_inc.php';

if (qg('uri')) {
	Files::redirectOldWikiPage(qg('uri'));
	throw new UserException('Page introuvable');
}

$cats = new Membres\Categories;
$categorie = $cats->get($user->id_categorie);

$homepage = Config::getInstance()->get('admin_homepage');

$banner = null;
Plugin::fireSignal('accueil.banniere', ['user' => $user, 'session' => $session], $banner);

$tpl->assign(compact('categorie', 'homepage', 'banner'));

$tpl->display('admin/index.tpl');
flush();

// Si pas de cron on réalise les tâches automatisées à ce moment-là
// c'est pas idéal mais mieux que rien
if (!USE_CRON)
{
	require_once ROOT . '/scripts/cron.php';
}
