<?php

namespace Paheko;
use KD2\Test;

use Paheko\Users\Session;
use Paheko\UserTemplate\Functions;
use Paheko\Files\Files;
use Paheko\Entities\Module;

paheko_init();

Files::createFromString('documents/test.csv', "A,B\n1,2\n3,4\n");

$module = new Module;
$module->importForm([
	'label' => 'Test',
	'name' => 'uniquetest',
]);
$module->set('web', false);
$module->save();
$module->exportToIni();

Session::getInstance()->forceLogin(1);

Files::createFromString('modules/uniquetest/index.html', '{{:assign var="cols" a="A" b="B"}}{{:csv action="initialize" file="documents/test.csv" columns=$cols}}');

$tpl = $module->template('index.html');
$tpl->fetch();
