<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

if (qg('export') == 'plan')
{
	$comptes->exportPlan();
	exit;
}

$tpl->assign('confirm', qg('confirm'));

if (f('import') && $form->check('plan_import', ['upload' => 'file|required', 'format' => 'required|in:json']))
{
	try {
		$comptes->importPlan($_FILES['upload']['tmp_name'], true);
		Utils::redirect(ADMIN_URL . 'compta/comptes/?import&confirm=import');
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}
elseif (f('reset') && $form->check('plan_reset'))
{
	try {
		$comptes->importPlan(null, true);
		Utils::redirect(ADMIN_URL . 'compta/comptes/?import&confirm=reset');
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}

$classe = (int) qg('classe');

$tpl->assign('classe', $classe);

if (!$classe)
{
	$tpl->assign('classes', $comptes->listTree(0, false));
}
else
{
	$positions = $comptes->getPositions();

	$tpl->assign('classe_compte', $comptes->get($classe));
	$tpl->assign('liste', $comptes->listTree($classe));
}

function tpl_get_position($pos)
{
	global $positions;
	return $positions[$pos];
}

$tpl->register_modifier('get_position', 'Garradin\tpl_get_position');

$template = 'index';

if ($classe) {
	$template = 'classe';
}
elseif (qg('import') !== null) {
	$template = 'import';
	$tpl->assign('confirm', qg('confirm'));
}

$tpl->display(sprintf('admin/compta/comptes/%s.tpl', $template));
