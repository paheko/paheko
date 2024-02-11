<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Email\Mailings;
use Paheko\Entities\Email\Mailing;

require_once __DIR__ . '/_inc.php';

$id = intval($_GET['id'] ?? 0);

if ($id !== 0) {
	$mailing = Mailings::get($id);

	if (!$mailing) {
		throw new UserException('Invalid mailing ID');
	}
}
else {
	$mailing = new Mailing;
}

$csrf_key = 'mailing_edit';

$form->runIf('save', function () use ($mailing) {
	$mailing->importForm();
	$mailing->set('body', trim(f('content') ?? ''));
	$mailing->save();

	$js = false !== strpos($_SERVER['HTTP_ACCEPT'] ?? '', '/json');

	$url = '!users/email/mailing/details.php?id=' . $mailing->id;
	$url = Utils::getLocalURL($url);

	if ($js) {
		die(json_encode(['success' => true, 'modified' => time(), 'redirect' => $url]));
	}

	Utils::redirect($url);
}, $csrf_key);

if (!$form->hasErrors()) {
	$form->runIf('content', function() use ($mailing) {
		$mailing->set('body', trim(f('content') ?? ''));
		echo $mailing->getHTMLPreview(null, true);
		exit;
	});
}

$tpl->assign(compact('mailing', 'csrf_key'));

$tpl->assign('custom_js', ['web_editor.js']);
$tpl->assign('custom_css', ['web.css', BASE_URL . 'content.css']);

$tpl->display('users/email/mailing/edit.tpl');
