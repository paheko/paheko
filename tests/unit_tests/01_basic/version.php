<?php

namespace Garradin;
use KD2\Test;

Test::assert(function_exists('Garradin\garradin_version'));
Test::assert(garradin_version());