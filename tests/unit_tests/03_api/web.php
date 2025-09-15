<?php

namespace Paheko;

use KD2\Test;

require __DIR__ . '/_inc.php';

$c = api('POST', 'web/test-new', ['title' => 'Test coucou', 'content' => 'Coucou **test**']);

Test::isArray($c);
Test::assert(count($c) > 0);
$c = (object) $c;

Test::assert($c->title === 'Test coucou');

$c = api('GET', 'web/test-new', ['html' => true]);
Test::isArray($c);
Test::assert(count($c) > 0);
$c = (object) $c;

Test::assert($c->content === 'Coucou **test**');
Test::assert($c->title === 'Test coucou');
Test::assert($c->html === '<div class="web-content"><p>Coucou <strong>test</strong></p></div>');

$c = api('GET', 'web');
Test::isArray($c);
Test::assert(count($c) === 1);
$c = current($c);
Test::isInstanceOf(\stdClass::class, $c);
Test::assert(isset($c->title));
Test::assert($c->title === 'Test coucou');

$c = api('PUT', 'web/test-new/coucou.txt', [], 'coucou!!!');
Test::isArray($c);
Test::assert(count($c) > 0);
$c = (object) $c;

Test::assert($c->success === true);

$c = api('GET', 'web/test-new/attachments');
Test::isArray($c);
Test::assert(count($c) > 0);
$c = current($c);
Test::isArray($c);
Test::assert(isset($c['name']));
Test::assert($c['name'] === 'coucou.txt');
Test::assert(isset($c['size']));
Test::assert($c['size'] === 9);

$c = api('DELETE', 'web/test-new/coucou.txt');
Test::isArray($c);
Test::assert(count($c) > 0);
$c = (object) $c;

Test::assert($c->success === true);

$c = api('DELETE', 'web/test-new');
Test::isArray($c);
Test::assert(count($c) > 0);
$c = (object) $c;

Test::assert($c->success === true);

try {
	$c = api('GET', 'web/test-new');
}
catch (APIException $e) {
	Test::assert($e->getCode() === 404);
}
