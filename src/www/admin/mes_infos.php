<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$data = $session->getUser();
$champs = Config::getInstance()->get('champs_membres')->getList();

$tpl->assign(compact('champs', 'data'));

$tpl->display('admin/mes_infos.tpl');
