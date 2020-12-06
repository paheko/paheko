<?php
namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

// L'utilisateur est déjà connecté
if ($session->isLogged())
{
    Utils::redirect(ADMIN_URL . '');
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

$champs = $config->get('champs_membres');
$id_field = (object) $champs->get($config->get('champ_identifiant'));
$id_field_name = $id_field->title;

$form->runIf('login', function () use ($id_field_name, $session) {
    if (!trim(f('_id'))) {
        throw new UserException(sprintf('L\'identifiant (%s) n\'a pas été renseigné.', $id_field_name));
    }

    if (!trim(f('password'))) {
        throw new UserException('Le mot de passe n\'a pas été renseigné.');
    }

    if (!$session->login(f('_id'), f('password'), (bool) f('permanent'))) {
        throw new UserException(sprintf("Connexion impossible.\nVérifiez votre identifiant (%s) et votre mot de passe.", $id_field_name));
    }
}, 'login', ADMIN_URL);

$tpl->assign('ssl_enabled', empty($_SERVER['HTTPS']) ? false : true);
$tpl->assign('prefer_ssl', (bool)PREFER_HTTPS);
$tpl->assign('own_https_url', str_replace('http://', 'https://', utils::getSelfURL()));

$tpl->assign(compact('id_field_name'));
$tpl->assign('changed', qg('changed') !== null);

$tpl->display('admin/login.tpl');