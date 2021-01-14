<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

use Garradin\Web;
use Garradin\Entities\Web\Page;
use Garradin\Entities\Files\File;
use Garradin\Files\Files;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_WEB, Membres::DROIT_ECRITURE);

$page = Web::get((int) qg('page'));

if (!$page) {
	throw new UserException('Page inconnue');
}

$csrf_key = 'attach_' . $page->id();

$form->runIf('delete', function () use ($page) {
	$file = Files::get((int) f('delete'));

	if (!$file->getLinkedId($file::LINK_FILE) == $page->id()) {
		throw new UserException('Ce fichier n\'est pas lié à cette page');
	}

	$file->delete();
}, $csrf_key, Utils::getSelfURI());


$form->runIf(f('upload') || f('uploadHelper_mode'), function () use ($page) {
	if (f('uploadHelper_status') > 0) {
		throw new UserException('Un seul fichier peut être envoyé en même temps.');
	}

	$file = File::upload('file');

	// Lier le fichier à la page wiki
	$file->linkTo(File::LINK_FILE, $page->id());

	if (f('uploadHelper_status') !== null)
	{
		$uri = Utils::getSelfURI() . '&sent';
		echo json_encode([
			'redirect'  =>  $uri,
			'callback'  =>  'insertHelper',
			'file'      =>  [
				'image' =>  (int)$file->image,
				'id'    =>  (int)$file->id(),
				'nom'   =>  $file->name,
				'thumb' =>  $file->image ? $file->thumb_url() : false
			],
		]);
		exit;
	}
}, $csrf_key, Utils::getSelfURI() . '&sent');

if (f('uploadHelper_mode') !== null && $form->hasErrors()) {
	echo json_encode(['error' => implode(PHP_EOL, $form->getErrorMessages())]);
	exit;
}


$files = $page->getAttachmentsGallery(true);
$images = $page->getImageGallery(true);
$max_size = Utils::getMaxUploadSize();

$tpl->assign(compact('page', 'files', 'images', 'max_size', 'csrf_key'));
$tpl->assign('sent', (bool)qg('sent'));

$tpl->assign('custom_js', ['upload_helper.js', 'wiki_fichiers.js']);

$tpl->display('web/_attach.tpl');
