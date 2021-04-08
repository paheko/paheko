<?php

namespace Garradin;

use Garradin\Web\Web;
use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$homepage = Config::getInstance()->get('admin_homepage');

$banner = null;
Plugin::fireSignal('accueil.banniere', ['user' => $user, 'session' => $session], $banner);

if ($homepage && ($file = Files::get($homepage))) {
	$homepage = $file->render(['prefix' => ADMIN_URL . 'common/files/preview.php?p=' . File::CONTEXT_DOCUMENTS . '/']);
}
else {
	$homepage = null;
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
