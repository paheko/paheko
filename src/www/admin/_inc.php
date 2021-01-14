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
$tpl->assign('admin_url', ADMIN_URL);

$form = new Form;
$tpl->assign_by_ref('form', $form);

$session = Session::getInstance();
$config = Config::getInstance();

$tpl->assign('session', $session);
$tpl->assign('config', $config->getConfig());

if (!defined('Garradin\LOGIN_PROCESS'))
{
    if (!$session->isLogged())
    {
        if ($session->isOTPRequired())
        {
            Utils::redirect(ADMIN_URL . 'login_otp.php');
        }
        else
        {
            Utils::redirect(ADMIN_URL . 'login.php');
        }
    }

    $tpl->assign('is_logged', true);

    $user = $session->getUser();
    $tpl->assign('user', $user);

    $tpl->assign('current', '');

    if ($session->get('plugins_menu') === null)
    {
        // Construction de la liste de plugins pour le menu
        // et stockage en session pour ne pas la recalculer à chaque page
        $session->set('plugins_menu', Plugin::listMenu($user));
    }

    $tpl->assign('plugins_menu', $session->get('plugins_menu'));
}
