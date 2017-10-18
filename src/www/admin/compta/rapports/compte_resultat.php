<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$tpl->assign('compte_resultat', $rapports->compteResultat($criterias));

$tpl->display('admin/compta/rapports/compte_resultat.tpl');
