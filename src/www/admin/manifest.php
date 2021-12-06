<?php
namespace Garradin;

const LOGIN_PROCESS = true;
require_once __DIR__ . '/_inc.php';

$manifest = [
	'background_color' => 'white',
	'description'      => 'Gestion de l\'association',
	'display'          => 'fullscreen',
	'name'             => $config->nom_asso,
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
			'src'   => $config->fileURL('icon'),
			'type'  => 'image/png',
			'purpose' => 'any maskable',
		],
	],
];

header('Content-Type: text/json; charset=utf-8');
echo json_encode($manifest, JSON_PRETTY_PRINT);
