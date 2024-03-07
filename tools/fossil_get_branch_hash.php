#!/usr/bin/env php
<?php

$url = rtrim($_SERVER['argv'][1] ?? '', '/');

if (empty($url)) {
	printf("Usage: %s URL BRANCH\n", $_SERVER['argv'][0]);
	exit(1);
}

$branch = $_SERVER['argv'][2] ?? 'trunk';
$html = file_get_contents($url . '/info/' . $branch);

if (!preg_match('!<span id="hash-ci">(.*?)</span>!', $html, $match)) {
	echo "Cannot find hash\n";
	exit(1);
}

$hash = trim(strip_tags($match[1]));

if (strlen($hash) !== 40 && strlen($hash) !== 64) {
	echo "Wrong hash length\n";
	exit(1);
}

if (!preg_match('!^[0-9a-f]+$!', $hash)) {
	echo "Wrong hash: " . $hash . "\n";
	exit(1);
}

echo $hash;
