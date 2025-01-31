<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Entities\Accounting\Chart;

const SKIP_STARTUP_CHECK = true;

require_once __DIR__ . '/../../include/test_required.php';
require_once __DIR__ . '/../../include/init.php';

$exists = file_exists(DB_FILE);

if ($exists && !filesize(DB_FILE)) {
	@unlink(DB_FILE);
	$exists = false;
}

if ($exists) {
	throw new UserException('Paheko est déjà installé');
}

Install::checkAndCreateDirectories();
Install::checkReset();

if (DISABLE_INSTALL_FORM) {
	throw new \RuntimeException('Install form has been disabled');
}

function f($key)
{
	return \KD2\Form::get($key);
}

$tpl = Template::getInstance();
$tpl->assign('admin_url', ADMIN_URL);

$form = new Form;
$tpl->assign_by_ref('form', $form);
$csrf_key = 'install';

$form->runIf('save', function () {
	Install::installFromForm();
	Session::getInstance()->forceLogin(1);
}, $csrf_key, ADMIN_URL);

$tpl->assign('countries', Chart::COUNTRY_LIST);
$tpl->assign('require_admin_account', !is_array(LOCAL_LOGIN));

$tpl->assign(compact('csrf_key'));

$tpl->display('install.tpl');
