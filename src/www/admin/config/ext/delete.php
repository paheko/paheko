<?php
namespace Paheko;

use Paheko\UserTemplate\Modules;
use Paheko\Plugins;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'ext_delete';
$plugin = $module = null;
$mode = qg('mode');

if (qg('plugin')) {
	$plugin = Plugins::get(qg('plugin'));

	if ($plugin->enabled) {
		throw new UserException('Impossible de supprimer une extension activée');
	}

	$form->runIf(f('delete') && f('confirm_delete'), function () use ($plugin) {
		$plugin->delete();
	}, $csrf_key, '!config/ext/');
}
else {
	$module = Modules::get(qg('module'));

	if ($module->enabled) {
		throw new UserException('Impossible de supprimer un module activé');
	}

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
		}
	}, $csrf_key, '!config/ext/');
}

$tpl->assign(compact('plugin', 'module', 'csrf_key', 'mode'));

$tpl->display('config/ext/delete.tpl');
