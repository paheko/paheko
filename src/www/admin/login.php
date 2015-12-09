<?php
namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

if ($membres->isLogged())
{
    Utils::redirect('/admin/');
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

if (Utils::post('login'))
{
    if (!Utils::CSRF_check('login'))
    {
        $error = 'OTHER';
    }
    else
    {
        if (Utils::post('id') && Utils::post('passe')
            && $membres->login(Utils::post('id'), Utils::post('passe')))
        {
            Utils::redirect('/admin/');
        }

        $error = 'LOGIN';
    }
}

$champs = $config->get('champs_membres');

$champ = $champs->get($config->get('champ_identifiant'));

$tpl->assign('ssl_enabled', empty($_SERVER['HTTPS']) ? false : true);
$tpl->assign('prefer_ssl', (bool)PREFER_HTTPS);
$tpl->assign('own_https_url', str_replace('http://', 'https://', utils::getSelfURL()));

$tpl->assign('champ', $champ);
$tpl->assign('error', $error);

$tpl->display('admin/login.tpl');