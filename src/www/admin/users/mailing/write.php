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

// Preview of mailing content
if (!$form->hasErrors()) {
	if (!empty($_POST['content']) && isset($_GET['preview'])) {
		try {
			$mailing->set('body', trim(f('content') ?? ''));
			echo $mailing->getHTMLPreview(null, true);
			exit;
		}
		catch (TemplateException $e) {
			echo '<h2 style="color: red;">' . htmlspecialchars($e->getMessage()) . '</h2>';
			exit;
		}
	}
}

$tpl->assign(compact('mailing', 'csrf_key'));

$tpl->assign('custom_js', ['web_editor.js']);
$tpl->assign('custom_css', ['web.css', BASE_URL . 'content.css']);

$tpl->display('users/mailing/write.tpl');
