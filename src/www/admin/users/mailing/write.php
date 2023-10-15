<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Email\Mailings;

require_once __DIR__ . '/_inc.php';

$mailing = Mailings::get((int)qg('id'));

if (!$mailing) {
	throw new UserException('Invalid mailing ID');
}

$csrf_key = 'mailing_write';

$form->runIf('save', function () use ($mailing) {
	$mailing->importForm();
	$mailing->set('body', trim(f('content') ?? ''));
	$mailing->save();

	$js = false !== strpos($_SERVER['HTTP_ACCEPT'] ?? '', '/json');

	$url = '!users/mailing/details.php?id=' . $mailing->id;
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
$tpl->assign('custom_css', ['web.css', '!web/css.php']);

$tpl->display('users/mailing/write.tpl');
