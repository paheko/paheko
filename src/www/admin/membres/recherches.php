<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$recherche = new Recherche;

$tpl->assign('liste', $recherche->getList($user->id));


$tpl->display('admin/membres/recherches.tpl');
