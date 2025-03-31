<?php

namespace Paheko;

use KD2\Test;

paheko_init(null);

$db = DB::getInstance();
$db->connect(false);

// test exec
Test::assert($db->exec('CREATE TABLE test (a, b);'));

// test insert
Test::assert($db->insert('test', ['a' => 1, 'b' => 2]));
Test::assert($db->insert('test', ['a' => 3, 'b' => 4]));

// test insert object
Test::assert($db->insert('test', (object) ['a' => 9, 'b' => 10]));

// test fetch
Test::equals((object)['a' => 1, 'b' => 2], $db->first('SELECT a, b FROM test;'));
Test::assert(is_object($db->first('SELECT a, b FROM test;')));
Test::equals(1, $db->firstColumn('SELECT a, b FROM test;'));
Test::equals(3, $db->firstColumn('SELECT COUNT(*) FROM test;'));

// test update
Test::assert($db->update('test', ['a' => 5, 'b' => 6], 'a = :a AND b = :b', ['a' => 3, 'b' => 4]));

// test update with mixed type bindings
try {
	$db->update('test', ['a' => 5, 'b' => 6], 'a = ? AND b = ?', [3, 4]);
	$failed = false;
}
catch (\LogicException $e) {
	$failed = true;
}

Test::assert($failed === true);

// test if update worked
Test::equals((object)['a' => 5, 'b' => 6], $db->first('SELECT a, b FROM test LIMIT 1, 1;'));

// test delete
Test::assert($db->delete('test', 'a = ? AND b = ?', 5, 6));

// test if delete worked
Test::equals(2, $db->firstColumn('SELECT COUNT(*) FROM test;'));

// test insert again
Test::assert(is_bool($db->insert('test', ['a' => 3, 'b' => 4])));

Test::equals(3, $db->firstColumn('SELECT COUNT(*) FROM test;'));

// Test bindings
Test::equals(1, $db->firstColumn('SELECT a, b FROM test WHERE a = :a;', ['a' => 1]));
Test::equals((object) ['a' => 1, 'b' => 2], $db->first('SELECT a, b FROM test WHERE a = ?;', 1));

// test SELECT
$expected = [(object)['a' => 1, 'b' => 2], (object)['a' => 9, 'b' => 10]];
Test::equals($expected, $db->get('SELECT * FROM test LIMIT 2;'));

$expected = [1 => 2, 9 => 10];
Test::equals($expected, $db->getAssoc('SELECT * FROM test LIMIT 2;'));

$expected = [1 => (object) ['a' => 1, 'b' => 2], 9 => (object) ['a' => 9, 'b' => 10]];
Test::equals(json_encode($expected), json_encode($db->getGrouped('SELECT * FROM test LIMIT 2;')));

// test transactions
Test::assert($db->begin());

Test::assert($db->insert('test', ['a' => 42, 'b' => 43]));

Test::assert($db->insert('test', ['a' => 44, 'b' => 45]));

// test rollback
Test::assert($db->rollback());

Test::equals(3, $db->firstColumn('SELECT COUNT(*) FROM test;'));

// test successful commit
Test::assert($db->begin());

Test::assert($db->insert('test', ['a' => 42, 'b' => 43]));

Test::assert($db->commit());

Test::equals(4, $db->firstColumn('SELECT COUNT(*) FROM test;'));