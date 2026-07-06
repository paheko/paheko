<?php

namespace Paheko;
use Paheko\Accounting\Charts;

$db->beginSchemaUpdate();

$db->import(__DIR__ . '/1.3.22.sql');

Config::deleteInstance(); // reload
$config = Config::getInstance();

// Import SIRET/SIREN number
if (!$config->org_business_number
	&& preg_match('/(?:Numéro\s+)?SIRE[TN](?:\s*[:-])?\s*([0-9. -]+)/iu', $config->org_infos, $match)) {
	try {
		$config->importForm([
			'org_business_number' => $match[1],
			'org_infos' => trim(str_replace($match[0], '', $config->org_infos)),
		]);
		$config->save();
	}
	catch (\Exception $e) {
		// Ignore errors
	}
}

$db->commitSchemaUpdate();
