<?php

namespace Paheko;

use Paheko\UserException;
use Paheko\Users\Session;
use Paheko\Web\Web;
use Paheko\Web\Render\Render;
use Paheko\Entities\Web\Page;
use Paheko\Entities\Files\File;
use KD2\SimpleDiff;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_WEB, $session::ACCESS_WRITE);

$page = Web::getById(qg('id'));

if (!$page) {
	throw new UserException('Page inconnue');
}

if (qg('new') !== null && empty($_POST)) {
	$page->set('status', Page::STATUS_ONLINE);
}

$csrf_key = 'web_edit_' . $page->id();

$editing_started = f('editing_started') ?: $page->modified->getTimestamp();

if (f('cancel')) {
	Utils::redirect(ADMIN_URL . 'web/?parent=' . $page->parent);
}

$show_diff = false;
$page->content = trim(preg_replace("/\r\n?/", "\n", $page->content));
$my_content = null;

$form->runIf('save', function () use ($page, $editing_started, &$show_diff, &$my_content) {
	$my_content = trim(preg_replace("/\r\n?/", "\n", (string)f('content')));

	try {
		if ($my_content !== $page->content && $editing_started < $page->modified->getTimestamp()) {
			$show_diff = true;

			if (qg('js') !== null) {
				http_response_code(400);
			}

			unset($_POST['content']);

			throw new UserException("La page a été modifiée par quelqu'un d'autre pendant que vous l'éditiez.\nVous allez devoir apporter à nouveau vos modifications au texte actuellement enregistré.");
		}

		$page->importForm();
		$page->saveNewVersion(Session::getUserId());
	}
	catch (UserException $e) {
		if (qg('js') !== null) {
			http_response_code(400);
			die(json_encode(['error' => $e->getMessage()]));
		}

		throw $e;
	}

	if (qg('js') !== null) {
		$url = Utils::getLocalURL('!web/?p=' . $page->path);
		die(json_encode(['success' => true, 'modified' => $page->modified->getTimestamp(), 'redirect' => $url]));
	}

	Utils::redirect('!web/?p=' . $page->path);
}, $csrf_key);

$restored_version = false;

if (($v = qg('restore')) && ($version = $page->getVersion((int)$v))) {
	$page->content = $version->content;
	$restored_version = true;
}

$parent_title = $page->parent ? Web::get($page->parent)->title : 'Racine du site';
$parent = [$page->parent => $parent_title];
$encrypted = f('encrypted') || $page->format == Render::FORMAT_ENCRYPTED;

$formats = $page::FORMATS_LIST;

$tpl->assign(compact(
	'page',
	'parent',
	'parent_title',
	'editing_started',
	'encrypted',
	'csrf_key',
	'my_content',
	'show_diff',
	'formats',
	'restored_version'
));

$tpl->assign('custom_js', [
	'web_editor.js',
	'web_encryption.js',
	//'block_editor.js',
]);
$tpl->assign('custom_css', ['web.css', '!web/css.php']);

$tpl->display('web/edit.tpl');
