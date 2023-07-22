<?php

namespace Paheko;

/*
	Dans la version 1.1.29, une mise à jour du plan comptable a écrasé les descriptions et types
	choisis par l'utilisateur. Ici nous essayons de restaurer ces valeurs à partir de la sauvegarde.
 */

$old_db = DATA_ROOT . '/association.pre-upgrade-1.1.29.sqlite';

if (!file_exists($old_db)) {
	return;
}

$db->exec(sprintf('ATTACH \'%s\' AS old;', $old_db));

$chart_id = $db->firstColumn('SELECT id FROM acc_charts WHERE code = \'PCA_2018\' AND country = \'FR\';');

// We cannot use UPDATE FROM as it doesn't work with old SQLite < 3.33.0
$db->exec(sprintf('UPDATE acc_accounts AS a
	SET description = (SELECT b.description FROM old.acc_accounts b WHERE b.id = a.id),
		type = (SELECT b.type FROM old.acc_accounts b WHERE b.id = a.id)
	WHERE a.id_chart = %d AND EXISTS (SELECT b.id FROM old.acc_accounts b WHERE b.id = a.id AND b.label = a.label AND b.code = a.code);', $chart_id));

$db->exec('DETACH \'old\';');
