<?php

namespace Paheko;

use Paheko\Web\Sync;

$db->beginSchemaUpdate();
$db->import(ROOT . '/include/migrations/1.3/1.3.0-rc14.sql');
$db->commitSchemaUpdate();

Sync::flatten();
