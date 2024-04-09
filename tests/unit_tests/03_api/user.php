<?php

namespace Paheko;

use KD2\Test;

require __DIR__ . '/_inc.php';

$c = api('GET', 'user/categories');
Test::isArray($c);
Test::assert(count($c) > 0);
$c = current($c);
Test::isInstanceOf(\stdClass::class, $c);
Test::assert(isset($c->name));
Test::assert(isset($c->id));
