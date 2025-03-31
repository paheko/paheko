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
	$lines[$code]['src_label'] = $line[1];
	$lines[$code]['src_position'] = $line[3];
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
	$lines[$code]['dst_label'] = $line[1];
	$lines[$code]['dst_position'] = $line[3];
}

fclose($fp);

uksort($lines, fn($a, $b) => strcmp(str_pad($a, 6, '0'), str_pad($b, 6, '0')));
//ksort($lines);

foreach ($lines as $code => $line) {
	$change = [];

	if (isset($line['dst_label']) && isset($line['src_label']) && compare_labels($line['src_label'], $line['dst_label'])) {
		$change[] = 'label';
	}

	if (isset($line['dst_position']) && isset($line['src_position']) && compare_labels($line['src_position'], $line['dst_position'])) {
		$change[] = 'position';
	}

	if (isset($line['dst_label']) && !isset($line['src_label'])) {
		$change[] = 'new';
	}
	elseif (!isset($line['dst_label']) && isset($line['src_label'])) {
		$change[] = 'deleted';
	}

	if (empty($change)) {
		continue;
	}

	fputcsv(STDOUT, [
		$code,
		implode(',', $change),
		$line['src_label'] ?? '',
		$line['src_position'] ?? '',
		$line['dst_label'] ?? '',
		$line['dst_position'] ?? '',
	]);
}

function compare_labels(string $a, string $b): bool {
	$a = preg_replace('![^\w\pL\d\s]+!U', '', $a);
	$b = preg_replace('![^\w\pL\d\s]+!U', '', $b);
	return strnatcasecmp($a, $b) !== 0;
}