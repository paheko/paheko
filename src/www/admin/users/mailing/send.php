<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Email\Mailings;

require_once __DIR__ . '/_inc.php';

$mailing = Mailings::get((int)qg('id'));

if (!$mailing) {
	throw new UserException('Invalid mailing ID');
}


if ($mailing->sent) {
	throw new UserException('This message has already been sent.');
}

$csrf_key = 'mailing_send';

$form->runIf('send', function() use ($mailing) {
	$mailing->send();
}, $csrf_key, '!users/mailing/details.php?sent&id=' . $mailing->id);

$tpl->assign(compact('mailing', 'csrf_key'));

$tpl->display('users/mailing/send.tpl');
