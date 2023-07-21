<?php
namespace Paheko;

use Paheko\Entities\Module;
use Paheko\Users\Session;
use Paheko\Entities\Users\Category;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'module_new';

$form->runIf('create', function () {
	$module = new Module;
	$module->importForm();
	$module->set('web', false);
	$module->save();
	$module->exportToIni();

	Utils::redirectDialog(sprintf('!config/ext/edit.php?module=%s', $module->name));
}, $csrf_key);


$types = [0 => 'Module normal', 1 => 'Site web'];
$sections = [null => '— Pas de restriction —'];

foreach (Category::PERMISSIONS as $section => $details) {
	$sections[$details['label']] = [];

	foreach ($details['options'] as $l => $label) {
		$sections[$details['label']][$section . '_' . $l] = $label;
	}
}

$tpl->assign(compact('csrf_key', 'sections', 'types'));

$tpl->display('config/ext/new.tpl');
