<?php

namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$comptes = new Compta\Comptes;

$tpl->assign('id_caisse', Compta\Comptes::CAISSE);
$tpl->assign('id_cheque_a_encaisser', Compta\Comptes::CHEQUE_A_ENCAISSER);
$tpl->assign('id_carte_a_encaisser', Compta\Comptes::CARTE_A_ENCAISSER);
