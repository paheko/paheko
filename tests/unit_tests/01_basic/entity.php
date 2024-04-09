<?php

namespace Paheko;
use KD2\Test;
use DateTime;

paheko_init(null);

$ts = strtotime('2024-03-01 00:00:00 UTC');

$dates = [
	'1/3/2024'    => '2024-03-01 00:00:00',
	'1/03/2024'   => '2024-03-01 00:00:00',
	'01/3/2024'   => '2024-03-01 00:00:00',
	'01/03/2024'  => '2024-03-01 00:00:00',
	'01/03/2024 20:42:55' => '2024-03-01 20:42:55',
	'01/03/2024 20:42' => '2024-03-01 20:42:00',
	'2024/03/01'  => '2024-03-01 00:00:00',
	'2024/03/01 20:42' => '2024-03-01 20:42:00',
	'2024-03-01'  => '2024-03-01 00:00:00',
	'2024-03-01 20:42' => '2024-03-01 20:42:00',
	'01/03/24'    => '2024-03-01 00:00:00',
	'01/03/85'    => '1985-03-01 00:00:00',
	$ts           => '2024-03-01 00:00:00',
	'20240301'    => '2024-03-01 00:00:00',
];

foreach ($dates as $value => $expected) {
	test_date($value, $expected);
}

function test_date(string $value, string $expected)
{
	$date = Entity::filterUserDateValue($value);
	Test::isInstanceOf(DateTime::class, $date);
	Test::strictlyEquals($expected, $date->format('Y-m-d H:i:s'), 'Fail for: ' . $value);
}
