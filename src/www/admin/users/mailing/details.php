<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Email\Mailings;

require_once __DIR__ . '/_inc.php';

$mailing = Mailings::get((int)qg('id'));

if (!$mailing) {
	throw new UserException('Invalid mailing ID');
}

if (qg('preview') !== null) {
	echo $mailing->getHTMLPreview(qg('preview') ?: null, true);
	return;
}

$csrf_key = 'mailing_details';

$form->runIf('send', function() use ($mailing) {
	$mailing->send();
}, $csrf_key, '!users/mailing/details.php?sent&id=' . $mailing->id);

$tpl->assign(compact('mailing', 'csrf_key'));

$tpl->assign('sent', null !== qg('sent'));

$tpl->display('users/mailing/details.tpl');
