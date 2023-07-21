<?php

namespace Paheko;

const INSTALL_PROCESS = true;

require_once __DIR__ . '/../../include/test_required.php';
require_once __DIR__ . '/../../include/init.php';

if (!Upgrade::preCheck()) {
	throw new UserException('Aucune mise à jour à effectuer, tout est à jour :-)');
}

if (isset($_GET['next'])) {
	Upgrade::upgrade();

	Install::showProgressSpinner('!', 'Mise à jour terminée');
}
else {
	Install::showProgressSpinner('!upgrade.php?next',
		sprintf("Mise à jour de version :\n%s → %s", DB::getInstance()->version(), paheko_version())
	);
}
