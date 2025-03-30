<?php
namespace Paheko;

use Paheko\UserTemplate\Modules;
use Paheko\Plugins;

require_once __DIR__ . '/../_inc.php';

$ext = Extensions::get(qg('name'));

if (!$ext) {
	throw new UserException('Extension inconnue');
}

$mode = qg('mode');
$csrf_key = 'ext_delete_' . $ext->name;

if ($ext->type === 'plugin') {
	if ($ext->enabled) {
		throw new UserException('Impossible de supprimer une extension activée');
	}

	$form->runIf(f('delete') && f('confirm_delete'), function () use ($ext) {
		$ext->delete();
	}, $csrf_key, '!config/ext/');
}
else {
	$module = $ext->module;

	if ($mode === 'data' && !$module->canDeleteData()) {
		throw new UserException('Impossible de supprimer les données de ce module.');
	}
	elseif ($mode === 'reset' && !$module->canReset()) {
		throw new UserException('Impossible de remettre ce module à son état antérieur.');
	}
	elseif ($mode === 'delete' && !$module->canDelete()) {
		throw new UserException('Impossible de supprimer ce module.');
	}

	$form->runIf(f('delete') && f('confirm_delete'), function () use ($module, $mode) {
		if ($mode === 'data') {
			$module->deleteData();
		}
		elseif ($mode === 'reset') {
			$module->resetChanges();
		}
		else {
			$module->delete();
			Utils::redirectDialog('!config/ext/');
		}
	}, $csrf_key, '!config/ext/');
}

$tpl->assign(compact('ext', 'csrf_key', 'mode'));

$tpl->display('config/ext/delete.tpl');
