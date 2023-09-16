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

if (ENABLE_TECH_DETAILS && qg('dump_config')) {
	echo "<table>";
	foreach (get_defined_constants(false) as $key => $value) {
		if (strpos($key, 'Paheko\\') !== 0) {
			continue;
		}

		$key = str_replace('Paheko\\', '', $key);

		if ($key === 'SECRET_KEY') {
			$value = '***HIDDEN***';
		}

		printf("<tr><th style='text-align: left'>%s</th><td>%s</td></tr>\n", $key, var_export($value, true));
	}

	return;
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
]);

$tpl->display('config/index.tpl');
