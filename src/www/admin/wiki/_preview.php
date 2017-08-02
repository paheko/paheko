<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('wiki', Membres::DROIT_ECRITURE);

$tpl->assign('contenu', f('contenu'));

$tpl->display('admin/wiki/_preview.tpl');
