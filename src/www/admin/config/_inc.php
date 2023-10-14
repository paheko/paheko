<?php

namespace Paheko;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN);

$tpl->assign('custom_css', ['config.css']);
