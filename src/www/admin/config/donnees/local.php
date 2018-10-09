<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$s = new Sauvegarde;

if (f('create'))
{
    $form->check('backup_create');

    if (!$form->hasErrors())
    {
        try {
            $s->create();
            Utils::redirect(ADMIN_URL . 'config/donnees/local.php?ok=create');
        } catch (UserException $e) {
            $form->addError($e->getMessage());
        }
    }
}
if (f('restore'))
{
    $form->check('backup_manage');

    if (!$form->hasErrors())
    {
        try {
            $r = $s->restoreFromLocal(f('file'));
            Utils::redirect(ADMIN_URL . 'config/donnees/local.php?ok=restore&code=' . (int)$r);
        } catch (UserException $e) {
            $form->addError($e->getMessage());
        }
    }
}
elseif (f('remove'))
{
    $form->check('backup_manage');

    if (!$form->hasErrors())
    {
        try {
            $s->remove(f('file'));
            Utils::redirect(ADMIN_URL . 'config/donnees/local.php?ok=remove');
        } catch (UserException $e) {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('ok_code', qg('code'));
$tpl->assign('ok', qg('ok'));
$tpl->assign('liste', $s->getList());

$tpl->display('admin/config/donnees/local.tpl');
