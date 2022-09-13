<?php
namespace Garradin;

use KD2\HTTP;

use Garradin\Users\DynamicFields;
use Garradin\Users\Session;

use Garradin\ValidationException;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();

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

$api_login = qg('tok') ?? (f('token') ?? null);

// L'utilisateur est déjà connecté
if (!$api_login && $session->isLogged()) {
    Utils::redirect(ADMIN_URL . '');
}

$id_field = DynamicFields::get(DynamicFields::getLoginField());
$id_field_name = $id_field->label;

$form->runIf('login', function () use ($id_field_name, $session) {
    if (Log::isLocked()) {
        throw new UserException("Vous avez dépassé la limite de tentatives de connexion.\nMerci d'attendre 5 minutes avant de ré-essayer de vous connecter.");
    }

    if (!trim((string) f('id'))) {
        throw new UserException(sprintf('L\'identifiant (%s) n\'a pas été renseigné.', $id_field_name));
    }

    if (!trim((string) f('password'))) {
        throw new UserException('Le mot de passe n\'a pas été renseigné.');
    }

    if (!$session->login(f('id'), f('password'), (bool) f('permanent'))) {
        throw new UserException(sprintf("Connexion impossible.\nVérifiez votre identifiant (%s) et votre mot de passe.", $id_field_name));
    }

    if (f('token')) {
        try {
            if (f('token') == 'flow') {
                $data = $session->createAppCredentials();
            }
            else {
                $session->validateAppToken(f('token'));
            }
        }
        finally {
            // We don't want to be logged-in really
            $session->logout();
        }

        if ($data->redirect ?? null) {
            http_response_code(303);
            header('Location: ' . $data->redirect);
            exit;
        }

        Utils::redirect('!login.php?tok=ok');
    }

}, 'login', ADMIN_URL);

$ssl_enabled = HTTP::getScheme() == 'https';
$changed = qg('changed') !== null;

$tpl->assign(compact('id_field', 'ssl_enabled', 'changed', 'api_login'));

$tpl->display('login.tpl');
