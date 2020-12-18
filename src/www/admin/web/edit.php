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
$diff = null;

if (f('cancel')) {
	Utils::redirect(ADMIN_URL . 'web/?parent=' . $page->parent_id);
}

$form->runIf('save', function () use ($page, $editing_started, &$diff) {
	$editing_started = new \DateTime($editing_started);

	if ($editing_started < $page->modified) {
		$diff = SimpleDiff::diff($page->raw(), f('content'));
		throw new UserException('La page a été modifiée par quelqu\'un d\'autre.');
	}

	$page->importForm();
	$page->save();
}, $csrf_key, Utils::getSelfURI() . '#saved');

$parent = $page->parent_id ? [$page->parent_id => Web::get($page->parent_id)->title] : null;
$encrypted = f('encrypted') || $page->file()->type == Page::FILE_TYPE_ENCRYPTED;

$tpl->assign(compact('page', 'parent', 'editing_started', 'encrypted', 'csrf_key', 'diff'));

$tpl->assign('custom_js', ['wiki_editor.js', 'wiki-encryption.js']);
$tpl->assign('custom_css', ['wiki.css', 'scripts/wiki_editor.css']);

$tpl->display('web/edit.tpl');
