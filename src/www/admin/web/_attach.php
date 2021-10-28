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

	if (!$file || !$file->checkDeleteAccess($session)) {
		throw new UserException('Vous ne pouvez pas supprimer ce fichier');
	}

	$file->delete();

	Utils::redirect(Utils::getSelfURI());
}, $csrf_key);


$form->runIf(f('upload') || f('uploadHelper_mode'), function () use ($page) {
	if (f('uploadHelper_status') > 0) {
		throw new UserException('Un seul fichier peut être envoyé en même temps.');
	}

	$new_file = File::upload(Utils::dirname($page->file_path), 'file');

	if (f('uploadHelper_status') !== null)
	{
		$uri = Utils::getSelfURI() . '&sent';
		echo json_encode([
			'redirect'  =>  $uri,
			'callback'  =>  'insertHelper',
			'file'      =>  [
				'image' =>  $new_file->image,
				'name'  =>  $new_file->name,
				'thumb' =>  $new_file->image ? $new_file->thumb_url() : false
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
