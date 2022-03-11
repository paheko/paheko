<?php
namespace Garradin;

use Garradin\Users\DynamicFields;

require_once __DIR__ . '/../_inc.php';

$tpl->assign('fields', DynamicFields::getInstance()->all());
$tpl->display('admin/config/fields/index.tpl');
