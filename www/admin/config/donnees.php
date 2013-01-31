<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$s = new Sauvegarde;

if (utils::get('sauvegarde'))
{

}

$tpl->assign('liste', $s->getList());

$tpl->display('admin/config/donnees.tpl');

?>