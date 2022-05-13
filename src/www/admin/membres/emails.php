<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$tpl->assign('rejected', Categories::listRejectedUsers());

$tpl->display('admin/membres/emails.tpl');
