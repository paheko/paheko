<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$tpl->assign('journal', $rapports->journal($criterias));

$tpl->display('admin/compta/rapports/journal.tpl');
