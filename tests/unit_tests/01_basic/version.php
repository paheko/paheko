<?php

namespace Paheko;
use KD2\Test;

paheko_init(null);

Test::assert(function_exists('Paheko\paheko_version'));
Test::assert(paheko_version());