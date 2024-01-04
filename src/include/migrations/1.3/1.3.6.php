<?php

namespace Paheko;

use Paheko\Accounting\Charts;

Charts::resetRules(['FR']);

$db->beginSchemaUpdate();
$db->import(ROOT . '/include/migrations/1.3/1.3.6.sql');
$db->commitSchemaUpdate();

