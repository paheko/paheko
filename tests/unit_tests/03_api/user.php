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

$c = api('POST', 'user/new', ['nom' => 'Coucou']);
Test::isArray($c);
Test::assert(count($c) > 0);
$c = (object) $c;

Test::assert(isset($c->nom));
Test::strictlyEquals('Coucou', $c->nom);
Test::assert(isset($c->id));
