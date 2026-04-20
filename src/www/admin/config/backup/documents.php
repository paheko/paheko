<?php
namespace Paheko;

use Paheko\Web\Web;
use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$form->runIf('restore', function () {
	$target = $_POST['target'] ?? null;
		header('Content-Type: application/json');

	try {
		if (empty($target)) {
			throw new UserException('Erreur à la décompression du fichier : javascript doit être activé.');
		}

		// Decompress (inflate) raw data
		if (empty($_FILES['file1']['error']) && !empty($_FILES['file1']['tmp_name']) && f('compressed')) {
			$f = $_FILES['file1']['tmp_name'];
			$in = fopen($f, 'rb');
			$out = fopen($f . '.inflated', 'w');
			stream_filter_append($in, 'zlib.inflate', STREAM_FILTER_READ);
			$size = filesize($f);
			$total = 0;

			while (!feof($in)) {
				$data = fread($in, 8192);
				fwrite($out, $data);
				$total += strlen($data);

				// Suspicious file size
				if ($size && $total > $size*50) {
					@unlink($f . '.inflated');
					throw new UserException('Fichier invalide à la décompression : ' . $_FILES['file1']['name']);
				}
			}

			rename($f . '.inflated', $f);
		}

		Files::upload(Utils::dirname($target), 'file1', Session::getInstance());
	}
	catch (UserException $e) {
		echo json_encode(['success' => false, 'error' => $target . ': '. $e->getMessage()]);
		exit;
	}

	echo json_encode(['success' => true, 'error' => null]);
	exit;
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
