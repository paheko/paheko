<?php
namespace Paheko;

use Paheko\Accounting\Accounts;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$account = Accounts::get((int) qg('id'));

if (!$account) {
	throw new UserException("Le compte demandé n'existe pas.");
}

$chart = $account->chart();

if ($chart->archived) {
	throw new UserException("Il n'est pas possible de modifier un compte d'un plan comptable archivé.");
}

$can_edit = $account->canEdit();
$csrf_key = 'acc_accounts_edit_' . $account->id();

$form->runIf('edit', function () use ($account, $can_edit) {
	if (!$can_edit) {
		$account->importLimitedForm();
	}
	else {
		$account->importForm();
	}

	$account->save();

	$page = '';

	if (!$account->type) {
		$page = 'all.php';
	}

	chart_reload_or_redirect(sprintf('!acc/charts/accounts/%s?id=%d', $page, $account->id_chart));
}, $csrf_key);

if ($account->type) {
	$tpl->assign('code_base', $account->getNumberBase());
	$tpl->assign('code_value', $account->getNumberUserPart());
}

$tpl->assign(compact('account', 'can_edit', 'chart'));

$tpl->display('acc/charts/accounts/edit.tpl');
