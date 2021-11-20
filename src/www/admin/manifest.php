<?php
namespace Garradin;

const LOGIN_PROCESS = true;
require_once __DIR__ . '/_inc.php';

$manifest = [
	'background_color' => 'white',
	'description'      => 'Gestion de l\'association',
	'display'          => 'fullscreen',
	'name'             => $config->nom_asso,
	'start_url'        => '/',
	'icons'            => [
		[
			'sizes' => '16x16',
			'src'   => WWW_URL . 'favicon.png',
			'type'  => 'image/png',
		],
/*
		[
			'sizes' => '256x256',
			'src'   => WWW_URL . 'logo.png',
			'type'  => 'image/png',
		],
*/
	],
];

header('Content-Type: text/json; charset=utf-8');
echo json_encode($manifest, JSON_PRETTY_PRINT);
