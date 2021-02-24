<?php

namespace Garradin;

use Garradin\Files\Files;

require_once __DIR__ . '/_inc.php';

if (qg('uri')) {
	Files::redirectOldWikiPage(qg('uri'));
	throw new UserException('Page introuvable');
}

$homepage = Config::getInstance()->get('admin_homepage');

$banner = null;
Plugin::fireSignal('accueil.banniere', ['user' => $user, 'session' => $session], $banner);

if ($homepage) {
	$homepage = $homepage->render(['prefix' => ADMIN_URL . '?uri=']);
}

$tpl->assign(compact('homepage', 'banner'));

$tpl->assign('custom_css', ['!web/css.php']);

$tpl->display('admin/index.tpl');
flush();

// Si pas de cron on réalise les tâches automatisées à ce moment-là
// c'est pas idéal mais mieux que rien
if (!USE_CRON)
{
	require_once ROOT . '/scripts/cron.php';
}
