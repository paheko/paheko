<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$tpl->assign('bilan', $rapports->bilan($criterias));

$tpl->display('admin/compta/rapports/bilan.tpl');
