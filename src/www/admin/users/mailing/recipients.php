<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Email\Mailings;

require_once __DIR__ . '/_inc.php';

$mailing = Mailings::get((int)qg('id'));

if (!$mailing) {
	throw new UserException('Invalid mailing ID');
}

if (isset($_GET['export'])) {
	$mailing->export($_GET['export']);
	return;
}

$csrf_key = 'mailing';

if (!$mailing->sent) {
	$form->runIf('delete', function () use ($mailing) {
		$mailing->deleteRecipient((int)f('delete'));
	}, $csrf_key, '!users/mailing/recipients.php?id=' . $mailing->id);
}

$list = $mailing->getRecipientsList();
$list->loadFromQueryString();

$tpl->assign(compact('mailing', 'list', 'csrf_key'));

$tpl->display('users/mailing/recipients.tpl');
