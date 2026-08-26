<?php
namespace Paheko;

use Paheko\Accounting\Charts;
use Paheko\Users\Session;

$types = null; // Just to silence phpstan

require_once __DIR__ . '/_inc.php';

$chart = null;

if ($id = (int)qg('id')) {
	$chart = Charts::get($id);
}
elseif (CURRENT_YEAR_ID) {
	$year = $current_year;
	$chart = $year->chart();
}

if (!$chart) {
	throw new UserException('Aucun plan comptable spécifié');
}

$accounts = $chart->accounts();

$form->runIf('bookmark', function () use ($accounts) {
	Session::getInstance()->requireAccess(Session::SECTION_ACCOUNTING, Session::ACCESS_ADMIN);

	$b = f('bookmark');

	if (!is_array($b) || empty($b)) {
		return;
	}

	$id = key($b);
	$value = current($b);
	$a = $accounts->get($id);
	$a->bookmark = (bool) $value;
	$a->save();

	http_response_code(204);
	exit;
}, 'bookmark', Utils::getSelfURI());


$list = $accounts->list($types);
$list->loadFromQueryString();

$target = !isset($_GET['_dialog']) ? '_dialog=manage' : null;

$tpl->assign(compact('chart', 'list', 'target'));

$tpl->display('acc/charts/accounts/all.tpl');
