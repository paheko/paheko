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
elseif (!empty($_POST['recover']))
{
    if (!Utils::CSRF_check('recoverPassword'))
    {
        $error = 'OTHER';
    }
    else
    {
        if (trim(Utils::post('id')) && $membres->recoverPasswordCheck(Utils::post('id')))
        {
            Utils::redirect('/admin/password.php?sent');
        }

        $error = 'MAIL';
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
