<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$tpl->assign('livre', $rapports->grandLivre($criterias));

$tpl->display('admin/compta/rapports/grand_livre.tpl');
