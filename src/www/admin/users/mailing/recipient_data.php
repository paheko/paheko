<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Email\Mailings;

require_once __DIR__ . '/_inc.php';

$mailing = Mailings::get((int)qg('id'));

if (!$mailing) {
	throw new UserException('Invalid mailing ID');
}

$data = $mailing->getRecipientExtraData((int)qg('r'));

if (!$data) {
	throw new UserException('Ce destinataire n\'a aucune donnée.');
}

$tpl->assign(compact('mailing', 'data'));

$tpl->display('users/mailing/recipient_data.tpl');
