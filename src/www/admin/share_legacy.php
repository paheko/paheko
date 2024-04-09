<?php

namespace Paheko;

use Paheko\Files\Files;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

/**
 * @deprecated FIXME: deprecated, delete this file in 1.5.0
 */

$file = null;
$hash = $_GET['hash'] ?? null;
$password = $_POST['p'] ?? null;

if (isset($_GET['path'], $hash)) {
	$file = Files::get($_GET['path']);
}

if (!$file) {
	throw new UserException('Ce partage n\'existe plus ou est invalide.', 404);
}

if ($file->checkShareLink($hash, $password)) {
	$file->serve();
}
elseif ($file->checkShareLinkRequiresPassword($hash)) {
	$tpl = Template::getInstance();
	$has_password = (bool) $password;

	$tpl->assign(compact('has_password'));
	$tpl->display('share_password.tpl');
}
else {
	throw new UserException('Ce lien de partage est invalide.', 404);
}
