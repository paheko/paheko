<?php
namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

// L'utilisateur est déjà connecté
if ($session)
{
    Utils::redirect('/admin/');
}

// Relance session_start et renvoie une image de 1px transparente
if (isset($_GET['keepSessionAlive']))
{
    Session::refresh();

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    header('Content-Type: image/gif');
    echo base64_decode("R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==");

    exit;
}

$errors = [];
$fail = false;

// Soumission du formulaire
if (f('login'))
{
    $check = fc('login', [
        '_id'       => 'required|string',
        'passe'     => 'required|string',
        'permanent' => 'boolean',
    ], $errors);

    if ($check && ($fail = Membres\Session::login(f('_id'), f('passe'), (bool) f('permanent'))))
    {
        Utils::redirect('/admin/');
    }
}

$champs = $config->get('champs_membres');

$champ = $champs->get($config->get('champ_identifiant'));

$tpl->assign('ssl_enabled', empty($_SERVER['HTTPS']) ? false : true);
$tpl->assign('prefer_ssl', (bool)PREFER_HTTPS);
$tpl->assign('own_https_url', str_replace('http://', 'https://', utils::getSelfURL()));

$tpl->assign('champ', $champ);
$tpl->assign('form_errors', $errors);
$tpl->assign('fail', $fail);

$tpl->display('admin/login.tpl');