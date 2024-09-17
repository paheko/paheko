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
	echo $mailing->getHTMLPreview((int)qg('preview') ?: null, true);
	return;
}

$tpl->assign(compact('mailing'));

$tpl->assign('custom_css', [BASE_URL . 'content.css']);
$tpl->assign('sent', null !== qg('sent'));

$tpl->display('users/mailing/details.tpl');
