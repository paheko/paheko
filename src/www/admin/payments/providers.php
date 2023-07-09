<?php

namespace Garradin;

use Garradin\Payments\Providers;

require_once __DIR__ . '/../_inc.php';

$tpl->assign('custom_css', ['!web/css.php']);

$tpl->assign('providers', Providers::list());

$tpl->display('payments/providers.tpl');
