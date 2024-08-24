<?php

namespace Paheko;

use Paheko\Entities\Files\Share;
use Paheko\Files\Shares;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$session = Session::getInstance();
$session->requireAccess($session::SECTION_DOCUMENTS, $session::ACCESS_ADMIN);

Shares::prune();

$csrf_key = 'shares';

$form->runIf('delete', function () {
	$share = Shares::getByHashID($_POST['delete']);

	if (!$share) {
		throw new UserException('Ce partage n\'existe pas');
	}

	$share->delete();
}, $csrf_key, Utils::getSelfURI());

$sharing_options = Share::OPTIONS;
$list = Shares::getList();
$list->loadFromQueryString();

$tpl->assign(compact('csrf_key', 'list', 'sharing_options'));

$tpl->display('docs/shares.tpl');
