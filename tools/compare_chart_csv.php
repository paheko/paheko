<?php

if (count($_SERVER['argv']) < 3) {
	die("Usage: script.php FILE1.csv FILE2.csv\n");
}

$fp = fopen($_SERVER['argv'][1], 'r');
$lines = [];

while (!feof($fp)) {
	$line = fgetcsv($fp, null, ',');

	if (!$line) {
		continue;
	}

	$code = $line[0];
	$lines[$code] ??= [];
	$lines[$code]['src'] = $line;
}

fclose($fp);


$fp = fopen($_SERVER['argv'][2], 'r');
$target = [];

while (!feof($fp)) {
	$line = fgetcsv($fp, null, ',');

	if (!$line || count($line) < 3) {
		continue;
	}

	$code = $line[0];
	$lines[$code] ??= [];
	$lines[$code]['dst'] = $line;
}

fclose($fp);

uksort($lines, fn($a, $b) => strcmp(str_pad($a, 6, '0'), str_pad($b, 6, '0')));
//ksort($lines);

$columns = ['code', 'label', 'description', 'position', 'bookmark'];

foreach ($lines as $code => $line) {
	$changed = [];

	if (isset($line['src']) && !isset($line['dst'])) {
		$changed[] = 'deleted';
	}
	elseif (isset($line['dst']) && !isset($line['src'])) {
		$changed[] = 'new';
	}
	else {
		for ($i = 0; $i < 5; $i++) {
			if (compare_labels($line['src'][$i] ?? '', $line['dst'][$i] ?? '')) {
				$changed[] = $columns[$i];
			}
		}
	}

	if (empty($changed)) {
		$changed[] = '=';
		//continue;
	}

	$row = [$code, implode(',', $changed)];

	foreach ($line['src'] ?? ['', '', '', '', ''] as $v) {
		$row[] = $v;
	}

	foreach ($line['dst'] ?? ['', '', '', '', ''] as $v) {
		$row[] = $v;
	}

	fputcsv(STDOUT, $row);
}

function compare_labels(string $a, string $b): bool {
	$a = preg_replace('![^\w\pL\d\s]+!U', '', $a);
	$b = preg_replace('![^\w\pL\d\s]+!U', '', $b);
	return strnatcasecmp($a, $b) !== 0;
}