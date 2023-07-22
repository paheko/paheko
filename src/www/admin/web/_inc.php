<?php

namespace Paheko;

use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess(Session::SECTION_WEB, $session::ACCESS_READ);

$tpl->assign('custom_css', ['web.css']);
