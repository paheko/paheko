<?php

require_once __DIR__ . '/../../include/init.php';
require_once GARRADIN_ROOT . '/include/template.php';
require_once GARRADIN_ROOT . '/include/class.membres.php';

$membres = new Garradin_Membres;

if (!defined('GARRADIN_LOGIN_PROCESS'))
{
    if (!$membres->isLogged())
    {
        utils::redirect('/admin/login.php');
    }

    $tpl->assign('is_logged', true);
    $tpl->assign('user', $membres->getLoggedUser());
    $user = $membres->getLoggedUser();

    $tpl->assign('current', '');
}

?>