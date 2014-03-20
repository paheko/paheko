<?php

if (ini_get('phar.readonly'))
{
    die("La variable INI phar.readonly doit être positionnée à Off pour utiliser ce script.\n");
}

if (empty($argv[1]) || empty($argv[2]))
{
    die("Usage : " . basename(__FILE__) . " plugin.phar /path/to/plugin\n");
}

$phar_file = $argv[1];
$phar_name = basename($phar_file);

@unlink($phar_file);
@unlink($phar_file . '.gz');

$target = realpath($argv[2]);

$p = new Phar($phar_file);

$p->buildFromDirectory($target);

$p->compress(Phar::GZ);

rename($phar_file . '.gz', __DIR__ . '/../src/plugins/' . $phar_name);
