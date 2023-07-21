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

if (($chart->code && !$account->user) || !$account->canDelete()) {
	throw new UserException("Ce compte ne peut être supprimé car des écritures y sont liées (sur l'exercice courant ou sur un exercice déjà clôturé).\nSi vous souhaitez faire du ménage dans la liste des comptes il est recommandé de créer un nouveau plan comptable. Attention, il n'est pas possible de modifier le plan comptable d'un exercice ouvert.");
}

$csrf_key = 'acc_accounts_delete_' . $account->id();

$form->runIf('delete', function () use ($account) {
	$account->delete();

	chart_reload_or_redirect(sprintf('!acc/charts/accounts/?id=%d', $account->id_chart));
}, $csrf_key);

$tpl->assign(compact('account', 'csrf_key'));

$tpl->display('acc/charts/accounts/delete.tpl');
