<?php

namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$tpl->assign('garradin_website', WEBSITE);