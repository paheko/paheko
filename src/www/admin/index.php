<?php

namespace Garradin;

use Garradin\Web\Web;
use Garradin\Files\Files;
use Garradin\Users\Session;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$banner = '';
$session = Session::getInstance();
Plugin::fireSignal('home.banner', ['user' => $session->getUser(), 'session' => $session], $banner);

$homepage = Config::getInstance()->file('admin_homepage');

if ($homepage) {
	$homepage = $homepage->render(ADMIN_URL . 'common/files/preview.php?p=' . File::CONTEXT_DOCUMENTS . '/');
}
else {
	$homepage = null;
}

$buttons = [];
Plugin::fireSignal('home.button', ['user' => $session->getUser(), 'session' => $session], $buttons);

$tpl->assign(compact('homepage', 'banner', 'buttons'));

$tpl->assign('custom_css', ['!web/css.php']);

$tpl->display('index.tpl');
flush();

// Si pas de cron on réalise les tâches automatisées à ce moment-là
// c'est pas idéal mais mieux que rien
if (!USE_CRON)
{
	require_once ROOT . '/scripts/cron.php';
}
