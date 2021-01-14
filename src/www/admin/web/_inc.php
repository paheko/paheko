<?php

namespace Garradin;

use Garradin\Membres\Session;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess(Session::SECTION_WEB, Membres::DROIT_ACCES);

$tpl->assign('custom_css', ['wiki.css']);
