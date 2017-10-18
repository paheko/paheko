<?php

namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

if (!$session->isOTPRequired())
{
    Utils::redirect('/admin/');
}

$login = null;

if (f('login'))
{
    $form->check('otp', [
        'code' => 'numeric|required',
    ]);

    if (!$form->hasErrors() && ($login = $session->loginOTP(f('code'))))
    {
        Utils::redirect('/admin/');
    }
}

$tpl->assign('fail', $login === false);

$tpl->assign('time', time());

$tpl->display('admin/login_otp.tpl');
