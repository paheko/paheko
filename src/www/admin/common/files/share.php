<?php
namespace Paheko;

use Paheko\Entities\Files\Share;
use Paheko\Files\Files;
use Paheko\Files\Shares;
use Paheko\Users\Session;

require __DIR__ . '/../../_inc.php';

$file = Files::getByHashID(qg('h'));

if (!$file) {
	throw new UserException('Fichier inconnu', 404);
}

if (!$file->canShare()) {
	throw new UserException('Vous n\'avez pas le droit de partager ce fichier.');
}

$csrf_key = 'file_share_' . $file->hash_id;
$share = null;

$form->runIf('share', function () use ($file) {
	$share = Shares::create($file, Session::getInstance(), (int)f('option'), (int)f('ttl'), f('password'));
	$share->save();
	Utils::redirect(sprintf('!common/files/share.php?h=%s&s=%s', $file->hash_id, $share->hash_id));
}, $csrf_key);


if (!empty($_GET['s'])) {
	$share = Shares::getByHashID($_GET['s']);
}

$ttl_options = Share::TTL_OPTIONS;
$default_ttl = Share::DEFAULT_TTL;
$sharing_options = Share::OPTIONS;

$tpl->assign(compact('file', 'csrf_key', 'share', 'sharing_options', 'ttl_options', 'default_ttl'));

$tpl->display('common/files/share.tpl');
