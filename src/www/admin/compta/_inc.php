<?php

namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$comptes = new Compta\Comptes;

$tpl->assign('id_caisse', Compta\Comptes::CAISSE);
