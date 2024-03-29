<?php

namespace Paheko;

use Paheko\API;
use KD2\Test;

paheko_init();

function api(string $method, string $path, array $params = [])
{
	$api = new API($method, $path, $params);
	Test::isInstanceOf(API::class, $api);
	return $api->route();
}

$c = api('GET', 'user/categories');
Test::isArray($c);
Test::assert(count($c) > 0);
$c = current($c);
Test::isInstanceOf(\stdClass::class, $c);
Test::assert(isset($c->name));
Test::assert(isset($c->id));
