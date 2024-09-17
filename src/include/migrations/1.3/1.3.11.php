<?php

namespace Paheko;

use Paheko\Users\DynamicFields;

// Force rebuild of users_search after it has been modified by 1.3.10.sql
$df = DynamicFields::getInstance();
$df->reload();
$df->rebuildSearchTable();
