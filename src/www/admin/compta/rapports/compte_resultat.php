<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$tpl->assign('compte_resultat', $rapports->compteResultat($criterias, [6, 7]));
$tpl->assign('compte_nature', $rapports->compteResultat($criterias, [86, 87]));

$tpl->display('admin/compta/rapports/compte_resultat.tpl');
