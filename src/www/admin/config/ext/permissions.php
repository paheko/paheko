<?php
namespace Paheko;

use Paheko\Extensions;
use Paheko\Users\Session;
use Paheko\Entities\Users\Category;

require_once __DIR__ . '/../_inc.php';

$ext = Extensions::get(qg('name'));

if (!$ext) {
	throw new UserException('Extension inconnue');
}

$csrf_key = 'ext_permissions_' . $ext->name;

$form->runIf('save', function() use ($ext) {
	if (!$ext->ini->allow_user_restrict || !$ext->restrict_section || !$ext->restrict_level) {
		throw new UserException('Cette extension ne permet pas de modifier les droits d\'accès.');
	}

	$restrict = explode('_', $_POST['restrict'] ?? 'config_9');
	$ext->changeRestrictedAccess($restrict[0] ?? 'config', intval($restrict[1] ?? 9));
}, $csrf_key, sprintf('!config/ext/details.php?name=%s&permissions_saved', $ext->name));


$module = $ext->module;
$current_permission = $ext->restrict_section . '_' . $ext->restrict_level;
$tpl->assign(compact('ext', 'current_permission', 'csrf_key'));

$tpl->display('config/ext/permissions.tpl');
