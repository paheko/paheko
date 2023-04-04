<?php
namespace Garradin;

use Garradin\UserTemplate\Modules;
use Garradin\Users\Session;

require_once __DIR__ . '/../_inc.php';


$csrf_key = 'module_import';
$form->runIf('import', function () {
	if (!f('confirm')) {
		throw new UserException('Merci de cocher la case de confirmation.');
	}

	if (empty($_FILES['zip']['tmp_name'])) {
		throw new UserException('Aucun fichier reçu.');
	}

	$m = Modules::import($_FILES['zip']['tmp_name']);

	if (!$m) {
		throw new UserException('Un module avec ce nom unique existe déjà. Pour importer ce module, merci de supprimer ou remettre à zéro le module existant.');
	}

	$i = (int)!$m->enabled;
	Utils::redirectDialog(sprintf('!config/ext/?install=%d&focus=%s', $i, $m->name));
}, $csrf_key);

$tpl->assign(compact('csrf_key'));

$tpl->display('config/ext/import.tpl');
