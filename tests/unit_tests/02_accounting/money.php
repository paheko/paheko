<?php

namespace Garradin;
use KD2\Test;

require INIT;

Test::strictlyEquals(500, Utils::moneyToInteger('5'));
Test::strictlyEquals(442, Utils::moneyToInteger('4,42'));
Test::strictlyEquals(442, Utils::moneyToInteger('4.42'));
Test::strictlyEquals(4, Utils::moneyToInteger('0,04'));
Test::strictlyEquals(30, Utils::moneyToInteger('0,3'));
Test::strictlyEquals(202034, Utils::moneyToInteger('2020,34'));
