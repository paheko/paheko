<?php

namespace Paheko;
use KD2\Test;

require_once INIT;

Test::assert(defined('Paheko\ROOT'));
Test::assert(is_readable(ROOT));

Test::assert(defined('Paheko\PLUGINS_ROOT'));

Test::assert(defined('Paheko\DATA_ROOT'));

Test::assert(defined('Paheko\CACHE_ROOT'));
