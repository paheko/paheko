<?php

namespace Garradin;
use KD2\Test;

require __DIR__ . '/../init.php';

Test::assert(function_exists('Garradin\garradin_version'));
Test::assert(garradin_version());