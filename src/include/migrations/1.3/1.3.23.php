<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\UserTemplate\UserTemplate;

$db->beginSchemaUpdate();

$db->import(__DIR__ . '/1.3.23.sql');

$config = Config::getInstance();

// Import post code / city
if (!empty($config->org_address)
	&& preg_match('/^(.*)\v\h*(\d[\dAB]\d{3})\s+(.*)$/i', $config->org_address, $match)) {
	try {
		$config->importForm([
			'org_address' => trim($match[1]),
			'org_post_code' => $match[2],
			'org_city' => trim($match[3]),
		]);
		$config->save();
	}
	catch (UserException $e) {
		// Ignore errors
	}
}

// Update module files
foreach (Files::listRecursive(File::CONTEXT_MODULES, null, false) as $file) {
	if ($file->isDir()) {
		continue;
	}

	if (!UserTemplate::isTemplate($file->name)) {
		continue;
	}

	$content = $file->fetch();

	if (false === strpos($content, '$config.org_address')) {
		continue;
	}

	$content = str_replace('$config.org_address', '$config.org_full_address', $content);
	$file->setContent($content);
}

$db->commitSchemaUpdate();
