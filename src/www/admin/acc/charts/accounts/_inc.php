<?php

namespace Paheko;

use Paheko\Entities\Accounting\Account;

require_once __DIR__ . '/../../_inc.php';

$dialog_target = '_dialog=' . (Utils::getDialogTarget() ?? 'manage');

$types = null;
$types_arg = null;
$types_names = null;

// Filter only some types (if coming from a selector)
if (qg('types')) {
	$types = explode('|', qg('types'));
	$types = array_map('intval', $types);
	$types_arg = 'types=' . implode('|', $types);

	$types_names = count($types) ? array_intersect_key(Account::TYPES_NAMES, array_flip($types)) : [];
	$types_names = implode(', ', $types_names);
}

$tpl->assign(compact('types_arg', 'dialog_target', 'types_names'));

function chart_reload_or_redirect(string $url)
{
	global $types_arg;

	$dialog = Utils::getDialogTarget();

	if ($types_arg) {
		$url .= '&' . $types_arg;
	}

	if ($dialog === 'manage') {
		Utils::reloadParentFrame($url);
	}
	else {
		Utils::reloadSelfFrame($url);
	}
}
