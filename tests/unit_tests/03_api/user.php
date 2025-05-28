<?php

namespace Paheko;

use KD2\Test;

use Paheko\Entities\Services\Service;
use Paheko\Entities\Services\Fee;

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

$id = $c->id;

$service = new Service;
$service->importForm(['label' => 'Adhésion test', 'duration' => '365']);
$service->save();

$fee = new Fee;
$fee->importForm(['label' => 'Tarif réduit', 'amount' => '10', 'id_service' => $service->id()]);
$fee->save();

$c = api('POST', 'user/' . $id . '/subscribe', ['id_service' => $service->id(), 'id_fee' => $fee->id(), 'paid' => true]);

Test::isArray($c);
Test::assert(count($c) > 0);
$c = (object) $c;

Test::assert(isset($c->id));
Test::strictlyEquals(1000, $c->expected_amount);
Test::strictlyEquals(true, $c->paid);
Test::strictlyEquals($service->id(), $c->id_service);
Test::strictlyEquals($fee->id(), $c->id_fee);
Test::assert($c->expiry_date->format('Ymd') == $c->date->modify('+365 days')->format('Ymd'));
