<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

use Garradin\Web\Web;
use Garradin\Entities\Web\Page;
use Garradin\Entities\Files\File;
use Garradin\Files\Files;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_WEB, $session::ACCESS_WRITE);

$page = Web::get(qg('p') ?: '');

if (!$page) {
	throw new UserException('Page inconnue');
}

$csrf_key = 'attach_' . $page->id();

$form->runIf('delete', function () use ($page, $session) {
	$path = Utils::dirname($page->file_path) . '/' . f('delete');
	$file = Files::get($path);

	if (!$file || !$file->canDelete()) {
		throw new UserException('Vous ne pouvez pas supprimer ce fichier');
	}

	$file->delete();
}, $csrf_key);


$form->runIf('upload', function () use ($page) {
	$new_file = Files::uploadMultiple(Utils::dirname($page->file_path), 'file');
}, $csrf_key);

$files = $page->getAttachmentsGallery(true);
$images = $page->getImageGallery(true);
$max_size = Utils::getMaxUploadSize();

$tpl->assign(compact('page', 'files', 'images', 'max_size', 'csrf_key'));

$tpl->assign('custom_js', ['wiki_fichiers.js']);
$tpl->assign('custom_css', ['!static/scripts/wiki_editor.css']);

$tpl->display('web/_attach.tpl');
