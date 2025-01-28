<?php
namespace Paheko;

require_once __DIR__ . '/_inc.php';

use Paheko\Web\Web;
use Paheko\Entities\Web\Page;
use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_WEB, $session::ACCESS_WRITE);

$page = Web::get((int)qg('id'));

if (!$page) {
	throw new UserException('Page inconnue');
}

$csrf_key = 'attach_' . $page->id();

$form->runIf('delete', function () use ($page) {
	$path = $page->dir_path() . '/' . f('delete');
	$file = Files::get($path);

	if (!$file || !$file->canDelete()) {
		throw new UserException('Vous ne pouvez pas supprimer ce fichier');
	}

	$file->delete();
}, $csrf_key);


$form->runIf('upload', function () use ($page) {
	Files::uploadMultiple($page->dir_path(), 'file', Session::getInstance());
}, $csrf_key);

$files = null;
$images = null;

if (isset($_GET['files'])) {
	$files = $page->getAttachmentsGallery(true);
}

if (isset($_GET['images'])) {
	$images = $page->getImageGallery(true);
}

$max_size = Utils::getMaxUploadSize();

$tpl->assign(compact('page', 'files', 'images', 'max_size', 'csrf_key'));

$tpl->assign('custom_js', ['web_files.js']);
$tpl->assign('custom_css', ['!static/scripts/web_editor.css']);

$tpl->display('web/_attach.tpl');
