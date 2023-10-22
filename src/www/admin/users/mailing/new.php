<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Email\Mailings;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'create_mailing';

$target_type = f('target_type');

$form->runIf($target_type == 'all' || f('step3'), function () {
	$target_type = f('target_type');
	$target_value = f('target_value');
	$target_label = $_POST['labels'][$target_value] ?? null;

	if ($target_type !== 'all' && empty($target_value)) {
		throw new UserException('Aucune cible n\'a été sélectionnée.');
	}

	$m = Mailings::create(f('subject'), $target_type, $target_value, $target_label);
	Utils::redirectDialog('!users/mailing/write.php?id=' . $m->id());
}, $csrf_key);

$list = null;

if ($target_type) {
	$list = Mailings::listTargets($target_type);
}

$tpl->assign(compact('csrf_key', 'target_type', 'list'));

$tpl->display('users/mailing/new.tpl');
