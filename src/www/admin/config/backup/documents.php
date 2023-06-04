<?php
namespace Garradin;

use Garradin\Web\Web;
use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/../_inc.php';

$form->runIf('restore', function () {
	try {
		// Decompress (inflate) raw data
		if (empty($_FILES['file1']['error']) && !empty($_FILES['file1']['tmp_name']) && f('compressed')) {
			$f = $_FILES['file1']['tmp_name'];
			file_put_contents($f, gzinflate(file_get_contents($f), 1024*1024*1024));
		}

		File::upload(Utils::dirname(f('target')), 'file1');
	}
	catch (UserException $e) {
		die(json_encode(['success' => false, 'error' => f('target') . ': '. $e->getMessage()]));
	}

	die(json_encode(['success' => true, 'error' => null]));
}, 'files_restore');


// Download all files as ZIP
$form->runIf('download_files', function () {
	(new Sauvegarde)->dumpFilesZip();
	exit;
}, 'files_download');

$ok = qg('ok') !== null;
$failed = (int) qg('failed');

if ($ok) {
	// Reset
	$config = Config::getInstance();
	$config->updateFiles();
	$config->save();
	$tpl->assign(compact('config'));

	Web::sync(true);

	Static_Cache::clean(0);
}

$files_size = Files::getUsedQuota();

$tpl->assign(compact('files_size', 'failed', 'ok'));

$tpl->display('admin/config/backup/documents.tpl');
