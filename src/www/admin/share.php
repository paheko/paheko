<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Files\Shares;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$id = strtok($_GET['uri'] ?? '', '/');
$download = !empty(strtok(''));
$share = Shares::getByHashID($id);

if (!$share) {
	throw new UserException('Ce partage n\'existe pas, ou a expiré.', 404);
}

if ($share->password) {
	$auth = false;
	$password = $_POST['p'] ?? ($_SERVER['PHP_AUTH_PW'] ?? null);

	if (!empty($password) && $share->verifyPassword($password)) {
		$auth = true;
		setcookie('sh', $share->generateToken(), 0);
	}
	elseif (isset($_COOKIE['sh']) && $share->verifyToken($_COOKIE['sh'])) {
		$auth = true;
	}
	else {
		$tpl = Template::getInstance();
		$has_password = (bool) $password;

		$tpl->assign(compact('has_password'));
		$tpl->display('share_password.tpl');
		return;
	}
}

$file = $share->file();

if (!$file) {
	throw new UserException('Ce fichier n\'existe pas.', 404);
}

$download_url = $share->download_url($file);

if ($share->option === $share::DOWNLOAD
	|| $download
	|| !empty($_SERVER['PHP_AUTH_PW']))
{
	$file->serve($download ?: null);
	return;
}


if ($share->option === $share::EDIT && $file->canEditInShare()) {
	$object = $file->editorHTML();
}
elseif ($file->canPreview()) {
	$object = $file->previewHTML($download_url);
}
else {
	$object = null;
}

$tpl->assign(compact('file', 'share', 'download_url', 'object'));
$tpl->display('share.tpl');
