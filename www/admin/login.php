<?php

define('GARRADIN_LOGIN_PROCESS', true);
require_once __DIR__ . '/_inc.php';

$error = false;

if (!empty($_POST['login']))
{
    if (!utils::CSRF_check('login'))
    {
        $error = 'OTHER';
    }
    else
    {
        if (!empty($_POST['email']) && !empty($_POST['passe'])
            && $membres->login($_POST['email'], $_POST['passe']))
        {
            utils::redirect('/admin/');
        }

        $error = 'LOGIN';
    }
}

$tpl->assign('error', $error);

$tpl->display('admin/login.tpl');

?>