<?php

namespace Paheko;

use Paheko\UserException;
use Paheko\Web\Web;
use Paheko\Web\Render\Render;
use Paheko\Entities\Web\Page;
use Paheko\Entities\Files\File;
use KD2\SimpleDiff;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_WEB, $session::ACCESS_WRITE);

$page = Web::get(qg('p'));

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
$current_content = trim(preg_replace("/\r\n?/", "\n", $page->content));
$new_content = null;

$form->runIf('save', function () use ($page, $editing_started, &$show_diff, &$new_content, $current_content) {
	$new_content = trim(preg_replace("/\r\n?/", "\n", (string)f('content')));

	try {
		if ($new_content !== $current_content && $editing_started < $page->modified->getTimestamp()) {
			$show_diff = true;
			http_response_code(400);
			throw new UserException('La page a été modifiée par quelqu\'un d\'autre pendant que vous éditiez le contenu.');
		}

		$page->importForm();
		$page->save();
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

$parent_title = $page->parent ? Web::get($page->parent)->title : 'Racine du site';
$parent = [$page->parent => $parent_title];
$encrypted = f('encrypted') || $page->format == Render::FORMAT_ENCRYPTED;

$formats = $page::FORMATS_LIST;

$tpl->assign(compact('page', 'parent', 'parent_title', 'editing_started', 'encrypted', 'csrf_key', 'current_content', 'new_content', 'show_diff', 'formats'));

$tpl->assign('custom_js', [
	'web_editor.js',
	'web_encryption.js',
	//'block_editor.js',
]);
$tpl->assign('custom_css', ['web.css', '!web/css.php']);

$tpl->display('web/edit.tpl');
