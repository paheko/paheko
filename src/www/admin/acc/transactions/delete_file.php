<?php

namespace Garradin;

use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$csrf_key = sprintf('acc_delete_file_%d', qg('id'));
$redirect = sprintf(ADMIN_URL . 'acc/transactions/details.php?id=%d', qg('from'));

require __DIR__ . '/../../common/files/delete.php';
