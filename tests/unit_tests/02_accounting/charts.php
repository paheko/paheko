<?php

namespace Paheko;
use KD2\Test;

use Paheko\Accounting\Charts;
use Paheko\Entities\Accounting\Chart;

paheko_init();

foreach (Charts::listInstallable() as $code => $label) {
	$chart = Charts::install($code);
	Test::isInstanceOf(Chart::class, $chart);
	Test::assert($chart->exists());
	Test::assert($chart->id() >= 1);
}
