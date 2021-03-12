<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$s = new Sauvegarde;

if (qg('download')) {
	$s->dump(qg('download'));
	exit;
}

$form->runIf('create', function () use ($s) {
	$s->create();
}, 'backup_create', Utils::getSelfURI(['ok' => 'create']));

$form->runIf('restore', function () use ($s) {
	if (!f('selected')) {
		throw new UserException('Aucune sauvegarde sélectionnée');
	}

	$r = $s->restoreFromLocal(f('selected'));
	Utils::redirect(Utils::getSelfURI(['ok' => 'restore', 'code' => (int)$r]));
}, 'backup_manage');

$form->runIf('remove', function () use ($s) {
	if (!f('selected')) {
		throw new UserException('Aucune sauvegarde sélectionnée');
	}

	$s->remove(f('selected'));
}, 'backup_manage', Utils::getSelfURI(['ok' => 'remove']));

$tpl->assign('ok_code', qg('code'));
$tpl->assign('ok', qg('ok'));
$tpl->assign('list', $s->getList());

$tpl->display('admin/config/donnees/local.tpl');
