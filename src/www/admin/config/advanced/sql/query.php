<?php
namespace Paheko;

require_once __DIR__ . '/../../_inc.php';

$pragma = $_GET['pragma'] ?? null;
$query = $_GET['query'] ?? null;

$tpl->assign(compact('pragma', 'query'));

$form->runIf($pragma || $query, function () use ($tpl, $pragma, $query) {
	$query_time = microtime(true);
	$db = DB::getInstance();

	if ($pragma) {
		$query = '';
		$result = [];
		$result_header = null;

		if ($pragma == 'integrity_check') {
			$result = $db->get('PRAGMA integrity_check;');
		}
		elseif ($pragma == 'foreign_key_check') {
			$result = $db->get('PRAGMA foreign_key_check;') ?: [['no errors']];
		}
		elseif (ENABLE_TECH_DETAILS && $pragma == 'vacuum') {
			$db->disableSafetyAuthorizer();
			$result[] = ['Size before VACUUM: ' . Backup::getDBSize()];
			$db->exec('VACUUM;');
			$result[] = ['Size after VACUUM: ' . Backup::getDBSize()];
			$db->enableSafetyAuthorizer();
		}

		$result_count = count($result);
	}
	elseif (!empty($query)) {
		$s = Search::fromSQL($query);

		if (f('export')) {
			$s->export(f('export'), 'Requête SQL');
			return;
		}

		$result_count = $s->countResults();

		if ($result_count > 10000) {
			throw new UserException('Trop de résultats. Merci de limiter la requête à 10.000 résultats.');
		}

		$result = $s->iterateResults();
		$result_header = $s->getHeader();
	}
	else {
		$result = $result_count = $result_header = null;
	}

	$query_time = round((microtime(true) - $query_time) * 1000, 3);

	$tpl->assign(compact('result', 'result_header', 'result_count', 'query_time'));
});

$tpl->display('config/advanced/sql/query.tpl');
