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
$list = null;

$form->runIf('delete', function () {
	$share = Shares::getByHashID($_POST['delete']);

	if (!$share) {
		throw new UserException('Ce partage n\'existe pas');
	}

	$share->delete();
}, $csrf_key, Utils::getSelfURI());


$list = Shares::getListForFile($file);

$sharing_options = Share::OPTIONS;

$tpl->assign(compact('file', 'csrf_key', 'list', 'sharing_options'));

$tpl->display('common/files/shares_list.tpl');
