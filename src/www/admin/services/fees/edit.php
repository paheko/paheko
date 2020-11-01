<?php
namespace Garradin;

use Garradin\Services\Fees;
use Garradin\Accounting\Accounts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$fee = Fees::get((int) qg('id'));

if (!$fee) {
    throw new UserException("Ce tarif n'existe pas");
}

$service = $fee->service();
$csrf_key = 'fee_edit_' . $fee->id();

$form->runIf('save', function () use ($fee) {
    $fee->importForm();
    $fee->save();
}, $csrf_key, 'services/fees/?id=' . $service->id());

if ($fee->amount) {
    $amount_type = 1;
}
elseif ($fee->formula) {
    $amount_type = 2;
}
else {
    $amount_type = 0;
}

$account = $fee->id_account ? [$fee->id_account => Accounts::getSelectorLabel($fee->id_account)] : null;

$tpl->assign(compact('service', 'amount_type', 'fee', 'csrf_key', 'account'));

$tpl->display('services/fees/edit.tpl');
