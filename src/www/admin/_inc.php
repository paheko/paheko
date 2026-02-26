<?php

namespace Paheko;

use Paheko\Users\Session;

require_once __DIR__ . '/../../include/init.php';

/**
 * @deprecated use $_POST[$key] ?? null instead
 */
function f($key)
{
	return \KD2\Form::get($key);
}

/**
 * @deprecated Use $_GET[$key] ?? null instead
 */
function qg($key)
{
	return isset($_GET[$key]) ? $_GET[$key] : null;
}

$tpl = Template::getInstance();

$form = new Form;
$tpl->assign_by_ref('form', $form);

$session = Session::getInstance();
$config = Config::getInstance();

if (!defined('Paheko\LOGIN_PROCESS'))
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

	$tpl->assign('current', '');

	$tpl->assign('plugins_menu', Extensions::listMenu($session));
}

// Make sure we allow frames to work
header('X-Frame-Options: SAMEORIGIN', true);
