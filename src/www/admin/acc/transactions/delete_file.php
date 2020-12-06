<?php

namespace Garradin;

use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$csrf_key = sprintf('acc_delete_file_%d', qg('id'));
$redirect = sprintf(ADMIN_URL . 'acc/transactions/details.php?id=%d', qg('from'));

require __DIR__ . '/../../common/delete_file.php';
