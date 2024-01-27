<?php

namespace Paheko;

use Paheko\Accounting\Charts;

Charts::resetRules(['FR']);

$db->beginSchemaUpdate();
$db->import(ROOT . '/include/migrations/1.4/1.4.0.sql');
$db->commitSchemaUpdate();

