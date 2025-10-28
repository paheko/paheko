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

$count = $mailing->countRecipients();
$hints = $mailing->sent ? null : $mailing->getDelivrabilityHints();

$tpl->assign(compact('mailing', 'count', 'hints'));

$tpl->assign('custom_css', [BASE_URL . 'content.css']);
$tpl->assign('sent', null !== qg('sent'));

$tpl->display('users/email/mailing/details.tpl');
