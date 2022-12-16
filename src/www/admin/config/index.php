<?php
namespace Garradin;

use Garradin\Users\Categories;
use Garradin\Users\DynamicFields;
use Garradin\Files\Files;
use Garradin\Sauvegarde;
use Garradin\Entities\Files\File;

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
	'garradin_version' => garradin_version() . ' [' . (garradin_manifest() ?: 'release') . ']',
	'new_version'      => $latest,
	'php_version'      => phpversion(),
	'has_gpg_support'  => \KD2\Security::canUseEncryption(),
	'server_time'      => time(),
	'sqlite_version'   => \SQLite3::version()['versionString'],
	'countries'        => Utils::getCountryList(),
	'garradin_website' => WEBSITE,
	'quota_used'       => Files::getUsedQuota(),
	'quota_max'        => Files::getQuota(),
	'quota_left'       => Files::getRemainingQuota(),
	'backups_size'     => Sauvegarde::getAllBackupsTotalSize(),
]);

$tpl->display('config/index.tpl');
