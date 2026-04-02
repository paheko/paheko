<?php

use Paheko\UserTemplate\Modifiers;
use Paheko\UserTemplate\CommonModifiers;
use Paheko\UserTemplate\Functions;
use Paheko\UserTemplate\CommonFunctions;
use Paheko\UserTemplate\Sections;

require __DIR__ . '/../src/include/init.php';

$modifiers = array_merge(
	Modifiers::MODIFIERS_LIST,
	CommonModifiers::MODIFIERS_LIST,
	[
		'args',
		'cat',
		'count',
		'date_format',
		'strftime',
		'escape',
		'json_encode',
		'or',
		'rawurlencode',
		'nl2br',
		'strip_tags',
		'tolower',
		'toupper',
		'ucwords',
		'ucfirst',
		'lcfirst',
	],
);

$functions = array_merge(
	Functions::FUNCTIONS_LIST,
	CommonFunctions::FUNCTIONS_LIST,
);

$functions = array_map(fn($name) => ':' . $name, $functions);
$functions = array_flip($functions);
$functions = array_map(fn() => null, $functions);

// Native brindille functions
$functions[':debug'] = [];
$functions[':assign'] = ['var' => null, 'append' => null, 'from' => null, 'value' => null];

$sections = Sections::SECTIONS_LIST;

$sections = array_map(fn($name) => '#' . $name, $sections);
$sections = array_flip($sections);
$sections = array_map(fn() => null, $sections);

// Native brindille sections
$sections['#foreach'] = ['count' => null, 'from' => null, 'item' => null, 'key' => null];

foreach ($sections as $key => $value) {
	$sections['/' . substr($key, 1)] = null;
}

$tags = array_merge([
	'if' => null,
	'else' => null,
	'elseif' => null,
	'/if' => null,
	'literal' => null,
	'/literal' => null,
], $sections, $functions);

foreach ($tags as &$value) {
	$value ??= (object) [];
}

unset($value);

echo json_encode($tags, JSON_PRETTY_PRINT);
