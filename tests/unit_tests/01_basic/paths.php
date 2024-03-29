<?php

namespace Paheko;
use KD2\Test;

paheko_init(null);

Test::assert(defined('Paheko\ROOT'));
Test::assert(is_readable(ROOT));

Test::assert(defined('Paheko\PLUGINS_ROOT'));

Test::assert(defined('Paheko\DATA_ROOT'));

Test::assert(defined('Paheko\CACHE_ROOT'));
