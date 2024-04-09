<?php

function paheko_init(?string $db_path = ':memory:')
{
	$create_db = $db_path === ':memory:';
	define('Paheko\WWW_URI', '/');
	define('Paheko\WWW_URL', 'http://localhost/');
	define('Paheko\CONFIG_FILE', null);
	define('Paheko\DB_FILE', $db_path ?? ':memory:');

	if ($db_path === null || $create_db) {
		define('Paheko\INSTALL_PROCESS', true);
	}

	require __DIR__ . '/../src/include/init.php';

	if ($create_db) {
		\Paheko\Install::install('FR', 'Test', 'bohwaz', 'bohwaz@example.org', 'bohwaz@example.org');
	}
}

if (!empty($_SERVER['argv'][1]))
{
	require $_SERVER['argv'][1];
	exit(0);
}
else
{
	// Lister et exécuter tous les tests unitaires
	$dir = new RecursiveDirectoryIterator(__DIR__ . '/unit_tests');
	$iterator = new RecursiveIteratorIterator($dir);

	$files = new RegexIterator($iterator, '/^.*\.php$/i', RecursiveRegexIterator::GET_MATCH);
	$list = [];

	foreach ($files as $file) {
		$list[] = $file[0];
	}

	natcasesort($list);

	foreach ($list as $file) {
		if (str_starts_with(basename($file), '_')) {
			continue;
		}

		$cmd = sprintf('php %s %s', escapeshellarg(__FILE__), escapeshellarg($file));
		echo substr($file, strlen(__DIR__) + 1) . "\n";
		passthru($cmd);
	}
}
