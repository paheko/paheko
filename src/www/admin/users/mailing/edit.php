<?php
namespace Paheko;

use Paheko\Email\Mailings;

require_once __DIR__ . '/_inc.php';

$mailing = Mailings::get((int)qg('id'));

if (!$mailing) {
	throw new UserException('Invalid mailing ID');
}

$csrf_key = 'mailing_edit_' . $mailing->id();

$form->runIf('save', function () use ($mailing) {
	$mailing->importForm();
	$mailing->save();
}, $csrf_key, '!users/mailing/details.php?id=' . $mailing->id);

$forced_sender = MAIL_SENDER;
$tpl->assign('custom_css', ['mailing.css']);
$tpl->assign(compact('mailing', 'csrf_key', 'forced_sender'));

$tpl->display('users/mailing/edit.tpl');
