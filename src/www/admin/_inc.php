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

$tpl = Template::getInstance();
$tpl->assign('admin_url', WWW_URL . 'admin/');

$session = Session::get();

if (!defined('Garradin\LOGIN_PROCESS'))
{
    if (!$session)
    {
        if (Session::isOTPRequired())
        {
            Utils::redirect('/admin/login_otp.php');
        }
        else
        {
            Utils::redirect('/admin/login.php');
        }
    }

    $tpl->assign('config', Config::getInstance()->getConfig());
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