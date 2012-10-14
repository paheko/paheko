<?php

require_once __DIR__ . '/_inc.php';

require_once GARRADIN_ROOT . '/include/class.compta_exercices.php';

$e = new Garradin_Compta_Exercices;

$tpl->assign('liste', $e->getList());
$tpl->assign('current', $e->getCurrent());

$tpl->display('admin/compta/exercices.tpl');

?>