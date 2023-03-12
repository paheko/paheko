<?php

namespace Garradin;

use Garradin\Web\Web;
use Garradin\Files\Files;
use Garradin\Users\Session;
use Garradin\Entities\Files\File;
use Garradin\Plugins;

require_once __DIR__ . '/_inc.php';

$banner = '';
$session = Session::getInstance();
Plugins::fireSignal('home.banner', ['user' => $session->getUser(), 'session' => $session], $banner);

$homepage = Config::getInstance()->file('admin_homepage');

if ($homepage) {
	$homepage = $homepage->render(ADMIN_URL . 'common/files/preview.php?p=' . File::CONTEXT_DOCUMENTS . '/');
}
else {
	$homepage = null;
}

$buttons = Plugins::listModulesAndPluginsHomeButtons($session);

$tpl->assign(compact('homepage', 'banner', 'buttons'));

$tpl->assign('custom_css', ['!web/css.php']);

$tpl->display('index.tpl');
flush();

// If no cron task is used, then the cron is run when visiting the homepage
// this is not the best, but better than nothing
if (!USE_CRON && @filemtime(CACHE_ROOT . '/last_cron_run') < (time() - 24*3600*3600)) {
	touch(CACHE_ROOT . '/last_cron_run');
	require_once ROOT . '/scripts/cron.php';
}
