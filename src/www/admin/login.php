<?php
namespace Garradin;

define('GARRADIN_LOGIN_PROCESS', true);
require_once __DIR__ . '/_inc.php';

// Relance session_start et renvoie une image de 1px transparente
if (isset($_GET['keepSessionAlive']))
{
    $membres->keepSessionAlive();

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    header('Content-Type: image/gif');
    echo base64_decode("R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==");

    exit;
}

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