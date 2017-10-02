<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$e = new Compta\Exercices;

$tpl->assign('liste', $e->getList());
$tpl->assign('current_exercice', $e->getCurrent());

$tpl->display('admin/compta/exercices/index.tpl');
