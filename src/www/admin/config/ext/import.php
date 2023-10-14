<?php
namespace Paheko;

use Paheko\UserTemplate\Modules;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';


$csrf_key = 'module_import';
$exists = false;

$form->runIf('import', function () use (&$exists) {
	if (empty($_FILES['zip']['tmp_name'])) {
		throw new UserException('Aucun fichier reçu.');
	}

	try {
		$m = Modules::import($_FILES['zip']['tmp_name'], !empty($_POST['overwrite']));
	}
	catch (\InvalidArgumentException $e) {
		throw new UserException($e->getMessage(), 0, $e);
	}

	if (!$m) {
		$exists = true;
		throw new UserException('Un module avec ce nom unique existe déjà. Pour écraser ce module, recommencer en cochant la case en bas du formulaire.');
	}

	$i = (int)!$m->enabled;
	Utils::redirectDialog(sprintf('!config/ext/?install=%d&focus=%s', $i, $m->name));
}, $csrf_key);

$tpl->assign(compact('csrf_key', 'exists'));

$tpl->display('config/ext/import.tpl');
