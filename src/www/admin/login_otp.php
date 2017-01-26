<?php
namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

if ($membres->isLogged() && !$membres->isOTPRequired())
{
    Utils::redirect('/admin/');
}

$error = false;

if (Utils::post('code'))
{
    if (!Utils::CSRF_check('otp'))
    {
        $error = 'OTHER';
    }
    else
    {
        if ($membres->loginOTP(Utils::post('code')))
        {
            Utils::redirect('/admin/');
        }

        $error = 'LOGIN';
    }
}

$tpl->assign('error', $error);

$tpl->assign('time', time());

$tpl->display('admin/login_otp.tpl');