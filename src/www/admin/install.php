<?php
namespace Garradin;

const INSTALL_PROCESS = true;

require_once __DIR__ . '/../../include/test_required.php';
require_once __DIR__ . '/../../include/init.php';

Install::checkAndCreateDirectories();

if (!file_exists(DB_FILE))
{
    // Renommage du fichier sqlite à la version 0.5.0
    $old_file = str_replace('.sqlite', '.db', DB_FILE);

    if (file_exists($old_file))
    {
        rename($old_file, DB_FILE);
        Utils::redirect(ADMIN_URL . 'upgrade.php');
    }
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
            'nom_asso'     => 'required',
            'email_asso'   => 'required|email',
            'nom_membre'   => 'required',
            'email_membre' => 'required|email',
            'passe'        => 'confirmed|required',
            'cat_membre'   => 'required',
        ]);

        if (!$form->hasErrors())
        {
            try {
            	Install::install(f('nom_asso'), f('adresse_asso'), f('email_asso'),
            		f('cat_membre'), f('nom_membre'), f('email_membre'), f('passe'),
            		WWW_URL);

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
