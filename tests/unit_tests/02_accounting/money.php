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

Test::strictlyEquals('5,50', Utils::money_format(550));
Test::strictlyEquals('0,05', Utils::money_format(5));
Test::strictlyEquals('0,50', Utils::money_format(50));
Test::strictlyEquals('1 000,50', Utils::money_format(100050));
