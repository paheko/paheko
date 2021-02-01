<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

use Garradin\Web\Web;
use Garradin\Entities\Web\Page;
use Garradin\Entities\Files\File;
use Garradin\Files\Files;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_WEB, $session::ACCESS_WRITE);

$page = Web::get((int) qg('page'));

if (!$page) {
	throw new UserException('Page inconnue');
}

$csrf_key = 'attach_' . $page->id();

$form->runIf('delete', function () use ($page) {
	$file = Files::get((int) f('delete'));

	if (!$file->checkContext($file::CONTEXT_FILE, $page->id())) {
		throw new UserException('Ce fichier n\'est pas lié à cette page');
	}

	$file->delete();
}, $csrf_key, Utils::getSelfURI());


$form->runIf(f('upload') || f('uploadHelper_mode'), function () use ($page) {
	if (f('uploadHelper_status') > 0) {
		throw new UserException('Un seul fichier peut être envoyé en même temps.');
	}

	$file = File::upload('file', File::CONTEXT_FILE, $page->id());

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

$tpl->assign('custom_js', ['upload_helper.min.js', 'wiki_fichiers.js']);
$tpl->assign('custom_css', ['!static/scripts/wiki_editor.css']);

$tpl->display('web/_attach.tpl');
