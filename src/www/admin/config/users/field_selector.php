<?php
namespace Paheko;

use Paheko\Users\DynamicFields;

require_once __DIR__ . '/../_inc.php';

$df = DynamicFields::getInstance();

$tpl->assign([
	'list' => $df->listEligibleNameFields(),
]);

$tpl->display('config/users/field_selector.tpl');
