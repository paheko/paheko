<?php

namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

if (trim(qg('c')))
{
    if (!$session->recoverPasswordCheck(qg('c')))
    {
        $form->addError('Le lien que vous avez suivi est invalide ou a expiré.');
    }
    else
    {
        if (f('change') && $form->check('changePassword'))
        {
            try {
                $session->recoverPasswordChange(qg('c'), f('passe'), f('passe_confirmed'));
                Utils::redirect('!login.php?changed');
            }
            catch (UserException $e) {
                $form->addError($e->getMessage());
            }
        }

        $tpl->assign('passphrase', Utils::suggestPassword());
        $tpl->display('admin/password_change.tpl');
        exit;
    }
}
elseif (f('recover'))
{
    $form->check('recoverPassword', [
        'id' => 'required'
    ]);

    if (!$form->hasErrors())
    {
        if (trim(f('id')) && $session->recoverPasswordSend(f('id')))
        {
            Utils::redirect(ADMIN_URL . 'password.php?sent');
        }

        $form->addError('Ce membre n\'a pas d\'adresse email enregistrée ou n\'a pas le droit de se connecter.');
    }
}

if (!$form->hasErrors() && null !== qg('sent'))
{
    $tpl->assign('sent', true);
}

$champs = $config->get('champs_membres');

$champ = $champs->get($config->get('champ_identifiant'));

$tpl->assign('champ', $champ);

$tpl->display('admin/password.tpl');
