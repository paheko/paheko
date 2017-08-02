<?php
namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$error = false;

if (trim(qg('c')))
{
    if ($membres->recoverPasswordConfirm(qg('c')))
    {
        Utils::redirect('/admin/password.php?new_sent');
    }

    $error = 'EXPIRED';
}
elseif (f('recover'))
{
    $form->check('recoverPassword', [
        'id' => 'required'
    ]);

    if (!$form->hasErrors())
    {
        if (trim(f('id')) && $membres->recoverPasswordCheck(f('id')))
        {
            Utils::redirect('/admin/password.php?sent');
        }

        $form->addError('Ce membre n\'a pas d\'adresse email enregistrée ou n\'a pas le droit de se connecter.');
    }
}

if (!$error && null !== qg('sent'))
{
    $tpl->assign('sent', true);
}
elseif (!$error && null !== qg('new_sent'))
{
    $tpl->assign('new_sent', true);
}


$champs = $config->get('champs_membres');

$champ = $champs->get($config->get('champ_identifiant'));

$tpl->assign('champ', $champ);

$tpl->assign('error', $error);

$tpl->display('admin/password.tpl');
