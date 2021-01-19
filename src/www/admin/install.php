<?php
namespace Garradin;

const INSTALL_PROCESS = true;

require_once __DIR__ . '/../../include/test_required.php';
require_once __DIR__ . '/../../include/init.php';

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

if (file_exists(DB_FILE))
{
    $tpl->assign('disabled', true);
}
else
{
    $tpl->assign('disabled', false);

    if (f('save'))
    {
        $form->check('install', [
            'nom_membre'   => 'required',
            'email_membre' => 'required|email',
            'passe'        => 'confirmed|required',
        ]);

        if (!$form->hasErrors())
        {
            try {
            	Install::install(f('nom_asso'), f('nom_membre'), f('email_membre'), f('passe'));

            	Utils::redirect(ADMIN_URL . 'login.php');
            }
            catch (UserException $e)
            {
                @unlink(DB_FILE);

                $form->addError($e->getMessage());
            }
        }
    }
}

$tpl->assign('passphrase', Utils::suggestPassword());
$tpl->display('admin/install.tpl');
