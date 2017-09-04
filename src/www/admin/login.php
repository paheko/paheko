<?php
namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

// L'utilisateur est déjà connecté
if ($session->isLogged())
{
    Utils::redirect('/admin/');
}

// Relance session_start et renvoie une image de 1px transparente
if (qg('keepSessionAlive') !== null)
{
    $session->keepAlive();

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    header('Content-Type: image/gif');
    echo base64_decode("R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==");

    exit;
}

$login = null;

// Soumission du formulaire
if (f('login'))
{
    $form->check('login', [
        '_id'       => 'required|string',
        'passe'     => 'required|string',
        'permanent' => 'boolean',
    ]);

    if (!$form->hasErrors() && ($login = $session->login(f('_id'), f('passe'), (bool) f('permanent'))))
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
$tpl->assign('fail', $login === false);

$tpl->display('admin/login.tpl');