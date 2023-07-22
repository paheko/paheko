<?php

namespace Paheko;

use Paheko\Entities\Accounting\Account;

require_once __DIR__ . '/../../_inc.php';

// Only open edit/delete/new actions in a dialog if we are not already in a dialog
$dialog_target = !isset($_GET['_dialog']) ? '_dialog=manage' : null;

$types = null;
$types_arg = null;
$types_names = null;

// Filter only some types (if coming from a selector)
if (qg('types')) {
	$types = explode(':', qg('types'));
	$types = array_map('intval', $types);
	$types_arg = 'types=' . implode(':', $types);

	$types_names = !empty($types) ? array_intersect_key(Account::TYPES_NAMES, array_flip($types)) : [];
	$types_names = implode(', ', $types_names);
}

$tpl->assign(compact('types_arg', 'dialog_target', 'types_names'));

function chart_reload_or_redirect(string $url)
{
	global $types_arg;

	if (($_GET['_dialog'] ?? null) === 'manage') {
		Utils::reloadParentFrame();
		return;
	}

	if ($types_arg) {
		$url .= '&' . $types_arg;
	}

	Utils::redirect($url);
}
