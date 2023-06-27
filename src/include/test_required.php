<?php

/*
 * Tests : vérification que les conditions pour s'exécuter sont remplies
 */

function test_required($condition, $message)
{
	if ($condition)
	{
		return true;
	}

	if (PHP_SAPI != 'cli')
	{
		header('Content-Type: text/html; charset=utf-8');
		echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Erreur</title>\n<meta charset=\"utf-8\" />\n";
		echo '<style type="text/css">body { font-family: sans-serif; } ';
		echo '.error { color: darkred; padding: .5em; margin: 1em; border: 3px double red; background: yellow; }</style>';
		echo "\n</head>\n<body>\n<h2>Erreur</h2>\n<h3>Le problème suivant empêche Paheko de fonctionner :</h3>\n";
		echo '<p class="error">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
		echo '<hr /><p>Pour plus d\'informations consulter ';
		echo '<a href="http://fossil.kd2.org/paheko/wiki?name=Probl%C3%A8mes%20fr%C3%A9quents">l\'aide sur les problèmes à l\'installation</a>.</p>';
		echo "\n</body>\n</html>";
	}
	else
	{
		echo "[ERREUR] Le problème suivant empêche Paheko de fonctionner :\n";
		echo $message . "\n";
		echo "Pour plus d'informations consulter http://fossil.kd2.org/paheko/wiki?name=Probl%C3%A8mes%20fr%C3%A9quents\n";
	}

	exit;
}

test_required(
	version_compare(phpversion(), '7.4', '>='),
	'PHP 7.4 ou supérieur requis. PHP version ' . phpversion() . ' installée.'
);

test_required(
	defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH,
	'L\'algorithme de hashage de mot de passe Blowfish n\'est pas présent (pas installé ou pas compilé).'
);

test_required(
	class_exists('\IntlDateFormatter') && function_exists('\idn_to_ascii'),
	'L\'extension "intl" n\'est pas installée mais est nécessaire (apt install php-intl).'
);

test_required(
	function_exists('\mb_strlen'),
	'L\'extension "mbstring" n\'est pas installée mais est nécessaire (apt install php-mbstring).'
);

test_required(
	class_exists('SQLite3'),
	'Le module de base de données SQLite3 n\'est pas disponible.'
);

$v = \SQLite3::version();

test_required(
	//$db->requireFeatures('cte', 'json_patch', 'fts4', 'date_functions_in_constraints', 'index_expressions', 'rename_column', 'upsert');
	// 3.25.0 = RENAME COLUMN + UPSERT
	version_compare($v['versionString'], '3.25', '>='),
	'SQLite3 version 3.25 ou supérieur requise. Version installée : ' . $v['versionString']
);

test_required(
	file_exists(__DIR__ . '/lib/KD2'),
	'Librairie KD2 non disponible.'
);

$db = new \SQLite3(':memory:');
$r = $db->query('PRAGMA compile_options;');
$options = [];
while ($row = $r->fetchArray(\SQLITE3_NUM)) {
	$options[] = $row[0];
}

test_required(
	in_array('ENABLE_FTS4', $options) || in_array('ENABLE_FTS3', $options),
	'Le module SQLite3 FTS4 (permettant de faire des recherches) n\'est pas installé ou activé.'
);

test_required(
	in_array('ENABLE_JSON1', $options)
	|| (version_compare($v['versionString'], '3.38', '>=') && !in_array('OMIT_JSON', $options)),
	'Le module SQLite3 JSON1 (utilisé dans les formulaires) n\'est pas installé.'
);

test_required(
	class_exists('Phar'),
	'Le module "Phar" n\'est pas disponible, il faut l\'installer.'
);
