<?php

namespace Paheko;

use Paheko\Users\Session;

require_once __DIR__ . '/../../include/init.php';

function f($key)
{
	return \KD2\Form::get($key);
}

function qg($key)
{
	return isset($_GET[$key]) ? $_GET[$key] : null;
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

	$tpl->assign('plugins_menu', Plugins::listModulesAndPluginsMenu($session));
}

// Make sure we allow frames to work
header('X-Frame-Options: SAMEORIGIN', true);
