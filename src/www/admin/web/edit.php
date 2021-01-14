<?php

namespace Garradin;

use Garradin\Web;
use Garradin\Entities\Web\Page;
use KD2\SimpleDiff;

require_once __DIR__ . '/_inc.php';

$id = (int) qg('id');
$page = Web::get($id);

if (!$page) {
	throw new UserException('Page inconnue');
}

$csrf_key = 'edit_' . $page->id();
$editing_started = f('editing_started') ?: date('Y-m-d H:i:s');

if (f('cancel')) {
	Utils::redirect(ADMIN_URL . 'web/?parent=' . $page->parent_id);
}

$show_diff = false;

$form->runIf('save', function () use ($page, $editing_started, &$show_diff) {
	$editing_started = new \DateTime($editing_started);

	if ($editing_started < $page->modified) {
		$show_diff = true;
		throw new UserException('La page a été modifiée par quelqu\'un d\'autre pendant que vous éditiez le contenu.');
	}

	$page->importForm();
	$page->save();
}, $csrf_key, Utils::getSelfURI() . '#saved');

$parent = $page->parent_id ? [$page->parent_id => Web::get($page->parent_id)->title] : null;
$encrypted = f('encrypted') || $page->file()->type == Page::FILE_TYPE_ENCRYPTED;

$old_content = f('content');
$new_content = $page->raw();

$tpl->assign(compact('page', 'parent', 'editing_started', 'encrypted', 'csrf_key', 'old_content', 'new_content', 'show_diff'));

$tpl->assign('custom_js', ['wiki_editor.js', 'wiki-encryption.js']);
$tpl->assign('custom_css', ['wiki.css', 'scripts/wiki_editor.css']);

$tpl->display('web/edit.tpl');
