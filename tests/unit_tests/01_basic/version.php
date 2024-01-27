<?php

namespace Paheko;
use KD2\Test;

require_once INIT;

Test::assert(function_exists('Paheko\paheko_version'));
Test::assert(paheko_version());