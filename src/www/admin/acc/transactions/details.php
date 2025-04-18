<?php
namespace Paheko;

use Paheko\Accounting\Transactions;
use Paheko\UserTemplate\Modules;

require_once __DIR__ . '/../_inc.php';

$transaction = Transactions::get((int) qg('id'));

if (!$transaction) {
	throw new UserException('Cette écriture n\'existe pas');
}

$csrf_key = 'details_' . $transaction->id();

if ($session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE)) {
	$form->runIf('mark_paid', function () use ($transaction) {
		$transaction->markPaid();
		$transaction->save();
	}, $csrf_key, Utils::getSelfURI());

	$form->runIf('mark_waiting', function () use ($transaction) {
		$transaction->markWaiting();
		$transaction->save();
	}, $csrf_key, Utils::getSelfURI());
}

$expert = !empty($session->user()->preferences->accounting_expert);

$variables = compact('csrf_key', 'transaction') + [
	'transaction_lines'    => $transaction->getLinesWithAccounts(),
	'transaction_year'     => $transaction->year(),
	'simple'               => isset($_GET['advanced']) ? !$_GET['advanced'] : !$expert,
	'details'              => $transaction->getDetails(),
	'files'                => $transaction->listFiles(),
	'creator_name'         => $transaction->getCreatorName(),
	'files_edit'           => $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE),
	'file_parent'          => $transaction->getAttachementsDirectory(),
	'linked_users'         => $transaction->listLinkedUsers(),
	'linked_transactions'  => $transaction->listLinkedTransactions(),
	'linked_subscriptions' => $transaction->listLinkedSubscriptions(),
];

$tpl->assign($variables);
$tpl->assign('snippets', Modules::snippetsAsString(Modules::SNIPPET_TRANSACTION, $variables));

$tpl->display('acc/transactions/details.tpl');
