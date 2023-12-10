<?php

namespace Paheko;

use Paheko\Web\Web;
use Paheko\Files\Files;
use Paheko\Users\Session;
use Paheko\Entities\Files\File;
use Paheko\Extensions;
use Paheko\Plugins;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();

$banner = '';
$signal = Plugins::fire('home.banner', false, ['user' => $session->getUser(), 'session' => $session]);

if ($signal) {
	$banner = implode('', $signal->getOut());
}

$homepage = Config::getInstance()->file('admin_homepage');

if ($homepage) {
	$homepage = $homepage->render();
}
else {
	$homepage = null;
}

$buttons = Extensions::listHomeButtons($session);
$has_extensions = empty($buttons) ? Extensions::isAnyExtensionEnabled() : true;

if (!$has_extensions && $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)) {
	$buttons = Extensions::listAvailableButtons();
}

$tpl->assign(compact('homepage', 'banner', 'buttons', 'has_extensions'));

$tpl->assign('custom_css', ['!web/css.php']);

$tpl->display('index.tpl');
flush();

// If no cron task is used, then the cron is run when visiting the homepage
// this is not the best, but better than nothing
if (!USE_CRON && @filemtime(CACHE_ROOT . '/last_cron_run') < (time() - 24*3600)) {
	touch(CACHE_ROOT . '/last_cron_run');
	require_once ROOT . '/scripts/cron.php';
}
