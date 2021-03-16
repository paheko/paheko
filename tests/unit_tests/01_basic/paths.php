<?php

namespace Garradin;
use KD2\Test;

require_once INIT;

Test::assert(defined('Garradin\ROOT'));
Test::assert(is_readable(ROOT));

Test::assert(defined('Garradin\PLUGINS_ROOT'));

Test::assert(defined('Garradin\DATA_ROOT'));

Test::assert(defined('Garradin\CACHE_ROOT'));
