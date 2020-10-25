<?php
namespace Garradin;

use Garradin\Services\Services;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$service = Services::get((int) qg('id'));

if (!$service) {
    throw new UserException("Cette activité n'existe pas");
}

if (f('save') && $form->check('service_edit_' . $service->id())) {
    try {
        $service->importForm();
        $service->save();

        Utils::redirect(ADMIN_URL . 'services/');
    }
    catch (UserException $e) {
        $form->addError($e->getMessage());
    }
}

if ($service->duration) {
    $period = 1;
}
elseif ($service->start_date) {
    $period = 2;
}
else {
    $period = 0;
}

$tpl->assign(compact('service', 'period'));

$tpl->display('services/edit.tpl');
