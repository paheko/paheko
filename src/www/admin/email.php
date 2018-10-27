<?php

namespace Garradin;

require_once __DIR__ . '/../../include/init.php';

$tpl = Template::getInstance();

if (!empty($_GET['optout']))
{
    $email = new Email;
    $email->setRejectedStatus($_GET['optout'], $email::REJET_OPTOUT, 'Demande de désinscription');
    
    $tpl->assign('title', 'Confirmation');
    $tpl->assign('error', 'Votre adresse a bien été désinscrite, vous ne recevrez plus de messages de notre part.');
}

$tpl->display('error.tpl');
