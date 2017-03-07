<?php

namespace Garradin;
use KD2\Test;

Test::assert(defined('Garradin\ROOT'));
Test::assert(is_readable(ROOT));

Test::assert(defined('Garradin\PLUGINS_ROOT'));
Test::assert(is_readable(PLUGINS_ROOT));

Test::assert(defined('Garradin\DATA_ROOT'));
Test::assert(is_readable(DATA_ROOT));
Test::assert(is_writeable(DATA_ROOT));

Test::assert(defined('Garradin\CACHE_ROOT'));
Test::assert(is_readable(CACHE_ROOT));
Test::assert(is_writeable(CACHE_ROOT));

