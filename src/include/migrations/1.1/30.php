<?php

namespace Garradin;

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

$db->exec(sprintf('UPDATE acc_accounts AS a
	SET description = b.description, type = b.type
	FROM old.acc_accounts AS b
	WHERE a.id = b.id AND a.id_chart = %d;', $chart_id));

$db->exec('DETACH \'old\';');
