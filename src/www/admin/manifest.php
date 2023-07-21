<?php
namespace Paheko;

const LOGIN_PROCESS = true;
require_once __DIR__ . '/_inc.php';

$config = Config::getInstance();

$manifest = [
	'background_color' => $config->color2 ?? ADMIN_COLOR2,
	'theme_color'      => $config->color1 ?? ADMIN_COLOR1,
	'description'      => 'Gestion de l\'association',
	'display'          => 'standalone',
	'name'             => $config->org_name,
	'start_url'        => ADMIN_URL,
	'icons'            => [
		[
			'sizes' => '32x32',
			'src'   => $config->fileURL('favicon'),
			'type'  => 'image/png',
			'purpose' => 'any maskable',
		],
		[
			'sizes' => '256x256',
			'src'   => $config->fileURL('icon', 'crop-256px'),
			'type'  => 'image/png',
			'purpose' => 'any maskable',
		],
	],
];

$body = json_encode($manifest, JSON_PRETTY_PRINT);

Utils::HTTPCache(md5($body), max($config->files['icon'], $config->files['favicon'], strtotime('2011-11-11')));

header('Content-Type: text/json; charset=utf-8');
echo $body;
