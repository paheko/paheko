<?php
namespace Paheko;

use Paheko\Web\Web;
use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$form->runIf('restore', function () {
	$target = $_POST['target'] ?? null;

	try {
		if (empty($target)) {
			throw new UserException('Erreur à la décompression du fichier : javascript doit être activé.');
		}

		// Decompress (inflate) raw data
		if (empty($_FILES['file1']['error']) && !empty($_FILES['file1']['tmp_name']) && f('compressed')) {
			$f = $_FILES['file1']['tmp_name'];
			file_put_contents($f, gzinflate(file_get_contents($f), 1024*1024*1024));
		}

		Files::upload(Utils::dirname($target), 'file1', Session::getInstance());
	}
	catch (UserException $e) {
		die(json_encode(['success' => false, 'error' => $target . ': '. $e->getMessage()]));
	}

	die(json_encode(['success' => true, 'error' => null]));
}, 'files_restore');


$ok = qg('ok') !== null;
$failed = (int) qg('failed');

if ($ok) {
	// Reset
	$config = Config::getInstance();
	$config->updateFiles();
	$config->save();
	$tpl->assign(compact('config'));
}

$tpl->assign(compact('failed', 'ok'));

$tpl->display('config/backup/documents.tpl');
