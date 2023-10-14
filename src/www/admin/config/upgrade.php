<?php
namespace Paheko;

use Paheko\Upgrade;
use KD2\Security;

require_once __DIR__ . '/_inc.php';

if (!ENABLE_UPGRADES) {
	Utils::redirect('/admin/');
	exit;
}

$i = Upgrade::getInstaller();

$csrf_key = 'upgrade_' . sha1(SECRET_KEY);
$releases = $i->listReleases();
$v = paheko_version();

// Remove releases that are in the past
foreach ($releases as $rv => $release) {
	if (!version_compare($rv, $v, '>')) {
		unset($releases[$rv]);
	}
}

$latest = $i->latest();
$tpl->assign('downloaded', false);
$tpl->assign('can_verify', Security::canUseEncryption());

$form->runIf('download', function () use ($i, $tpl) {
	$i->download(f('download'));
	$tpl->assign('downloaded', true);
	$tpl->assign('verified', $i->verify(f('download')));
	$tpl->assign('diff', $i->diff(f('download')));
	$tpl->assign('version', f('download'));
}, $csrf_key);

$form->runIf('upgrade', function () use ($i) {
	$i->upgrade(f('upgrade'));
	sleep(2);
	$url = ADMIN_URL . 'upgrade.php';
	printf('<h2>Cliquez ici pour terminer la mise a jour&nbsp;:</h2><form method="get" action="%s"><button type="submit">Continuer</button></form>', $url);
	exit;
}, $csrf_key);

$tpl->assign('website', WEBSITE);
$tpl->assign(compact('releases', 'latest', 'csrf_key'));
$tpl->display('config/upgrade.tpl');
