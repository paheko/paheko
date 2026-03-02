<?php

namespace Paheko;

use Paheko\API;
use KD2\Test;

paheko_init();

function api(string $method, string $path, array $params = [], ?string $input = null)
{
	$api = new API($method, $path, $params, false);
	Test::isInstanceOf(API::class, $api);
	$api->setAccessLevelByName('admin');
	$api->setInput($input);
	return $api->route();
}
