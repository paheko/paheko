<?php

namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

if (!Membres\Session::isOTPRequired())
{
    Utils::redirect('/admin/');
}

$login = null;

if (f('login'))
{
    $form->check('otp', [
        'code' => 'numeric|required',
    ]);

    if (!$form->hasErrors() && ($login = Membres\Session::loginOTP(Utils::post('code'))))
    {
        Utils::redirect('/admin/');
    }
}

//var_dump($form->hasErrors()); exit;

$tpl->assign('fail', $login === false);

$tpl->assign('time', time());

$tpl->display('admin/login_otp.tpl');
