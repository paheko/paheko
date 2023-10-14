<?php

namespace Garradin;
use KD2\Test;

require_once INIT;

Test::assert(function_exists('Garradin\paheko_version'));
Test::assert(paheko_version());