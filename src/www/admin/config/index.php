<?php
namespace Paheko;

use Paheko\Users\Categories;
use Paheko\Users\DynamicFields;
use Paheko\Files\Files;
use Paheko\Backup;
use Paheko\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

if (qg('check_version') !== null) {
	echo json_encode(Upgrade::fetchLatestVersion());
	exit;
}

$config = Config::getInstance();

$form->runIf('save', function () use ($config) {
	$config->importForm();
	$config->save();
}, 'config', Utils::getSelfURI(['ok' => '']));

$latest = Upgrade::getLatestVersion();

if (null !== $latest) {
	$latest = $latest->version;
}

$tpl->assign([
	'paheko_version'   => paheko_version() . ' [' . (paheko_manifest() ?: 'release') . ']',
	'new_version'      => $latest,
	'php_version'      => phpversion(),
	'has_gpg_support'  => \KD2\Security::canUseEncryption(),
	'server_time'      => time(),
	'sqlite_version'   => \SQLite3::version()['versionString'],
	'countries'        => Utils::getCountryList(),
	'paheko_website'   => WEBSITE,
	'quota_used'       => Files::getUsedQuota(),
	'quota_max'        => Files::getQuota(),
	'quota_left'       => Files::getRemainingQuota(),
	'backups_size'     => Backup::getAllBackupsTotalSize(),
	'versioning_policies' => Config::VERSIONING_POLICIES,
]);

$tpl->display('config/index.tpl');
