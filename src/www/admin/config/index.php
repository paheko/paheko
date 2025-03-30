<?php
namespace Paheko;

use Paheko\Users\Categories;
use Paheko\Users\DynamicFields;
use Paheko\Files\Files;
use Paheko\Backup;
use Paheko\Entities\Files\File;
use KD2\I18N\TimeZones;

require_once __DIR__ . '/_inc.php';

if (qg('check_version') !== null) {
	echo json_encode(Upgrade::fetchLatestVersion());
	exit;
}

if ($code = qg('tzlist')) {
	echo json_encode([
		'list' => TimeZones::listForCountry($code),
		'default' => TimeZones::getDefaultForCountry($code),
	]);
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
	'server_tz'        => date_default_timezone_get(),
	'sqlite_version'   => \SQLite3::version()['versionString'],
	'countries'        => Utils::getCountryList(),
	'timezones'        => TimeZones::listForCountry($config->country),
	'paheko_website'   => WEBSITE,
	'donate_url'       => DONATE_URL,
]);

$tpl->display('config/index.tpl');
