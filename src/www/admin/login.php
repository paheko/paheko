<?php
namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

if ($membres->isLogged())
{
    utils::redirect('/admin/');
}

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

if (utils::post('login'))
{
    if (!utils::CSRF_check('login'))
    {
        $error = 'OTHER';
    }
    else
    {
        if (utils::post('id') && utils::post('passe')
            && $membres->login(utils::post('id'), utils::post('passe')))
        {
            utils::redirect('/admin/');
        }

        $error = 'LOGIN';
    }
}

$champs = $config->get('champs_membres');

$champ = $champs->get($config->get('champ_identifiant'));

$tpl->assign('champ', $champ);
$tpl->assign('error', $error);

$tpl->display('admin/login.tpl');

?>