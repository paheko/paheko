<?php
namespace Garradin;

define('GARRADIN_LOGIN_PROCESS', true);
require_once __DIR__ . '/_inc.php';

$error = false;

if (trim(utils::get('c')))
{
    if ($membres->recoverPasswordConfirm(utils::get('c')))
    {
        utils::redirect('/admin/password.php?new_sent');
    }

    $error = 'EXPIRED';
}
elseif (!empty($_POST['recover']))
{
    if (!utils::CSRF_check('recoverPassword'))
    {
        $error = 'OTHER';
    }
    else
    {
        if (trim(utils::post('email')) && $membres->recoverPasswordCheck(utils::post('email')))
        {
            utils::redirect('/admin/password.php?sent');
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


$tpl->assign('error', $error);

$tpl->display('admin/password.tpl');

?>