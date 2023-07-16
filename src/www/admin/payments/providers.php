<?php

namespace Garradin;

use Garradin\Payments\Providers;

require_once __DIR__ . '/../_inc.php';

$tpl->assign('custom_css', ['!web/css.php']);

$tpl->assign([
	'providers' => Providers::list(),
	'manual_provider' => [ 'name' => Providers::MANUAL_PROVIDER, 'label' => Providers::MANUAL_PROVIDER_LABEL ]
]);

$tpl->display('payments/providers.tpl');
