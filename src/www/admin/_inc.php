<?php

namespace Garradin;

use Garradin\Membres\Session;

require_once __DIR__ . '/../../include/init.php';

// Redirection automatique en HTTPS si nécessaire
if (PREFER_HTTPS !== true && PREFER_HTTPS >= 2 && empty($_SERVER['HTTPS']) && empty($_POST))
{
    utils::redirect(str_replace('http://', 'https://', utils::getSelfURL()));
    exit;
}

function f($key)
{
    return \KD2\Form::get($key);
}

// Query-Validate: valider les éléments passés en GET
function qv(Array $rules)
{
    if (\KD2\Form::validate($rules, $errors, $_GET))
    {
        return true;
    }

    foreach ($errors as &$error)
    {
        $error = sprintf('%s: %s', $error['name'], $error['rule']);
    }

    throw new UserException(sprintf('Paramètres invalides (%s).', implode(', ',  $errors)));
}

function qg($key)
{
    return isset($_GET[$key]) ? $_GET[$key] : null;
}

$tpl = Template::getInstance();
$tpl->assign('admin_url', WWW_URL . 'admin/');

$form = new Form;
$tpl->assign_by_ref('form', $form);

$session = new Session;

$tpl->assign('session', $session);
$tpl->assign('config', Config::getInstance()->getConfig());

if (!defined('Garradin\LOGIN_PROCESS'))
{
    if (!$session->isLogged())
    {
        if ($session->isOTPRequired())
        {
            Utils::redirect('/admin/login_otp.php');
        }
        else
        {
            Utils::redirect('/admin/login.php');
        }
    }

    $tpl->assign('is_logged', true);

    $user = $session->getUser();
    $tpl->assign('user', $user);

    $tpl->assign('current', '');
    $tpl->assign('plugins_menu', Plugin::listMenu());

    if ($session->canAccess('membres', Membres::DROIT_ACCES))
    {
        $tpl->assign('nb_membres', (new Membres)->countAllButHidden());
    }
}