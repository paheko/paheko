<?php
namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$error = false;

if (trim(Utils::get('c')))
{
    if ($membres->recoverPasswordConfirm(Utils::get('c')))
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

if (!$error && isset($_GET['sent']))
{
    $tpl->assign('sent', true);
}
elseif (!$error && isset($_GET['new_sent']))
{
    $tpl->assign('new_sent', true);
}


$champs = $config->get('champs_membres');

$champ = $champs->get($config->get('champ_identifiant'));

$tpl->assign('champ', $champ);

$tpl->assign('error', $error);

$tpl->display('admin/password.tpl');

?>