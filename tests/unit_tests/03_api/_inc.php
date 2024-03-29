<?php

namespace Paheko;

use Paheko\API;
use KD2\Test;

paheko_init();

function api(string $method, string $path, array $params = [])
{
	$api = new API($method, $path, $params);
	Test::isInstanceOf(API::class, $api);
	$api->setAccessLevelByName('admin');
	return $api->route();
}
