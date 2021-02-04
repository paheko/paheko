<?php
namespace Garradin;

const INSTALL_PROCESS = true;

require_once __DIR__ . '/../../include/test_required.php';
require_once __DIR__ . '/../../include/init.php';

if (file_exists(DB_FILE))
{
    throw new UserException('Garradin est déjà installé');
}

try {
    Install::checkAndCreateDirectories();
}
catch (UserException $e) {
    echo $e->getMessage();
    exit;
}

function f($key)
{
    return \KD2\Form::get($key);
}

$tpl = Template::getInstance();
$tpl->assign('admin_url', ADMIN_URL);

$form = new Form;
$tpl->assign_by_ref('form', $form);

$form->runIf('save', function () {
    Install::installFromForm();
}, 'install', ADMIN_URL);

$tpl->display('admin/install.tpl');
