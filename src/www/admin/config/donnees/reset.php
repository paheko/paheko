<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$s = new Sauvegarde;

if (f('reset_ok'))
{
    $form->check('reset');

    if (!$form->hasErrors())
    {
        try {
            Install::reset($session, f('passe_verif'));
            Utils::redirect(ADMIN_URL . 'config/donnees/reset.php?ok');
        } catch (UserException $e) {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('ok', qg('ok'));

$tpl->display('admin/config/donnees/reset.tpl');
