<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Email\Mailings;

require_once __DIR__ . '/_inc.php';

$mailing = Mailings::get((int)qg('id'));

if (!$mailing) {
	throw new UserException('Invalid mailing ID');
}

$csrf_key = 'mailing_delete';

$form->runIf('delete', function () use ($mailing) {
	$mailing->delete();
	Utils::redirectParent('!email/mailing/?msg=DELETE');
}, $csrf_key);

$tpl->assign(compact('mailing', 'csrf_key'));
$tpl->display('email/mailing/delete.tpl');
