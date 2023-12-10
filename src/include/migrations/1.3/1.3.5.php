<?php

namespace Paheko;

$db->beginSchemaUpdate();
$db->import(ROOT . '/include/migrations/1.3/1.3.5.sql');
$db->commitSchemaUpdate();
