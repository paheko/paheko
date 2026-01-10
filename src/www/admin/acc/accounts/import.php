<?php
namespace Paheko;

use Paheko\Accounting\Accounts;
use Paheko\Accounting\CSV;
use Paheko\Accounting\Export;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$session = Session::getInstance();
$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);
$user = $session->getUser();

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

$year = $current_year;
$year->assertCanBeModified();

$account = Accounts::get((int)qg('id'));

if (!$account) {
	throw new UserException("Le compte demandé n'existe pas.");
}

if ($account->type !== $account::TYPE_BANK) {
	throw new UserException('Ce compte n\'est pas un compte bancaire');
}

$csrf_key = 'acc_import_' . $account->id();
$csv = new CSV($session, 'acc_import_account');
$transactions = null;
$import = $_POST['t']['import'] ?? null;

if (!empty($_GET['cancel'])) {
	$csv->clear();
	Utils::redirect(Utils::getSelfURI(['id' => $account->id()]));
}

$columns = [
	'label'       => 'Libellé',
	'date'        => 'Date',
	'reference'   => 'Numéro pièce comptable',
	'p_reference' => 'Référence paiement',
	'amount'      => 'Montant',
	'debit'       => 'Débit',
	'credit'      => 'Crédit',
];

$csv->setColumns($columns, $columns);
$csv->setMandatoryColumns(['date', 'label', ['amount', ['debit', 'credit']]]);

//if (empty($_POST))  $csv->clear();

$form->runIf(f('load') && isset($_FILES['file']['tmp_name']), function () use ($csv) {
	$csv->upload($_FILES['file']);
}, $csrf_key, Utils::getSelfURI());

$form->runIf('set_columns', function () use ($csv) {
	$csv->skip(intval($_POST['skip_first_line'] ?? 0));
	$csv->setTranslationTable($_POST['translation_table'] ?? []);
}, $csrf_key, Utils::getSelfURI());

if ($csv->ready()) {
	$transactions = $account->matchImportTransactions($year, $csv, $_POST['t'] ?? null);

	$form->runIf('save', function () use ($transactions, $csv) {
		$db = DB::getInstance();
		$db->begin();

		foreach ($transactions as $i => $t) {
			if (empty($_POST['t'][$i]['import'])) {
				continue;
			}

			try {
				$t->save();
			}
			catch (UserException $e) {
				throw new UserException(sprintf("Ligne %d : %s", $i, $e->getMessage()), $e->getCode(), $e);
			}
		}

		$db->commit();
		$csv->clear();
	}, $csrf_key, '!acc/accounts/journal.php?msg=IMPORT&id=' . $account->id());
}

$tpl->assign(compact('account', 'csrf_key', 'year', 'csv', 'transactions'));

$tpl->display('acc/accounts/import.tpl');
