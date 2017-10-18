<?php

if (empty($argv[1]))
{
	echo sprintf('Usage: %s PLUGIN_DIRECTORY' . PHP_EOL, $argv[0]);
	exit(1);
}

assert_options(ASSERT_ACTIVE, true);
assert_options(ASSERT_BAIL, true);

$dir = rtrim($argv[1], '/\\') . DIRECTORY_SEPARATOR;

assert(is_readable($dir), 'Le répertoire du plugin n\'est pas lisible');
assert(is_dir($dir), sprintf('%s n\'est pas un répertoire', $dir));
assert(file_exists($dir . 'garradin_plugin.ini'), sprintf('%s n\'est pas un répertoire de plugin Garradin', $dir));

$dir_iterator = new RecursiveDirectoryIterator(substr($dir, 0, -1), FilesystemIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
https://fossil.kd2.org/garradin-plugins/timeline
$base = realpath($dir);

foreach ($iterator as $file)
{
	if ($file->isDir())
	{
		// Skip directories
		continue;
	}

	$source = str_replace($base, '', $file->getRealPath());

    if ($file->getExtension() == 'php')
    {
    	check_php($file->getRealPath(), $source);
    }
    elseif ($file->getExtension() == 'tpl')
    {
    	check_smarty($file->getRealPath(), $source);
    }
}

function check_php($file, $source)
{
	static $deprecated_php_functions = [
		'->simpleQuerySingle',
		'->queryFetchAssocKey',
		'->queryFetchAssoc',
		'->queryFetch',
		'->simpleStatementFetchAssocKey',
		'->simpleStatementFetchAssoc',
		'->simpleStatementFetch',
		'->simpleStatement',
		'->escapeString',
		'->simpleExec',
		'->simpleUpdate',
		'->simpleInsert',
		'utils::get(',
		'utils::post(',
		'utils::CRSF',
	];

	$content = file_get_contents($file);

	foreach ($deprecated_php_functions as $func)
	{
		if (stripos($content, $func) !== false)
		{
			fputs(STDERR, sprintf('ERROR: %s: la fonction %s a été supprimée de Garradin.', $source, $func) . PHP_EOL);
		}
	}
}

function check_smarty($file, $source)
{
	$content = file_get_contents($file);

	if (preg_match('/\{.*`.*\}/sU', $content))
	{
		fputs(STDERR, sprintf('ERROR: %s: la syntaxe `$variable` est invalide dans Smartyer, utiliser le modifieur |args:$variable', $source) . PHP_EOL);
	}

	if (preg_match('/\{\s*(section|php|switch|insert|capture)/', $content, $match))
	{
		fputs(STDERR, sprintf('ERROR: %s: le bloc "%s" est absent dans Smartyer', $source, $match[1]) . PHP_EOL);
	}

	if (preg_match('/(\$smarty\.|\$tpl\.|\$templatelite\.)/', $content, $match))
	{
		fputs(STDERR, sprintf('ERROR: %s: les variables "%s" sont absentes dans Smartyer', $source, $match[1]) . PHP_EOL);
	}

	if (preg_match('/\{.*\|escape/sU', $content))
	{
		fputs(STDERR, sprintf('SUGGESTION: %s: le modifieur |escape n\'est plus nécessaire (escaping automatique)', $source) . PHP_EOL);
	}

}