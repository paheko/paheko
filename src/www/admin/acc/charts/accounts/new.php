<?php
namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Entities\Accounting\Line;
use Garradin\Accounting\Accounts;
use Garradin\Accounting\Charts;
use Garradin\Membres\Session;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$chart = Charts::get((int)qg('id'));

if (!$chart) {
	throw new UserException('Ce plan comptable n\'existe pas');
}

if ($chart->archived) {
	throw new UserException("Il n'est pas possible de modifier un plan comptable archivé.");
}

$accounts = $chart->accounts();

$account = new Account;
$account->bookmark = true;
$account->id_chart = $chart->id();

$type = f('type') ?? qg('type');

// Simple creation with pre-determined account type
if ($type !== null) {
	$account->type = (int)$type;
}
elseif (isset($types) && is_array($types) && count($types) == 1) {
	$account->type = (int)current($types);
}

$csrf_key = 'account_new';

$form->runIf('toggle_bookmark', function () use ($accounts, $chart) {
	$a = $accounts->get(f('toggle_bookmark'));
	$a->bookmark = true;
	$a->save();

	chart_reload_or_redirect('!acc/charts/accounts/?id=' . $chart->id());
}, $csrf_key);

$form->runIf('save', function () use ($account, $accounts, $chart, $current_year) {
	$db = DB::getInstance();

	$db->begin();
	$account->importForm();

	$account->id_chart = $chart->id();
	$account->user = true;
	$account->bookmark = (bool) f('bookmark');
	$account->save();

	if (!empty(f('opening_amount')) && $current_year) {
		$t = new Transaction;
		$t->label = 'Solde d\'ouverture du compte';
		$t->id_creator = Session::getInstance()->getUser()->id;
		$t->date = clone $current_year->start_date;
		$t->type = $t::TYPE_ADVANCED;
		$t->notes = 'Créé automatiquement à l\'ajout du compte';
		$t->id_year = $current_year->id;

		$opening_account = $accounts->getOpeningAccountId();
		$amount = Utils::moneyToInteger(f('opening_amount'));
		$a = $amount > 0 ? 0 : abs($amount);
		$b = $amount < 0 ? 0 : abs($amount);
		$t->addLine(Line::create($account->id, $a, $b));
		$t->addLine(Line::create($opening_account, $b, $a));
		$t->save();
	}

	$db->commit();

	$page = '';

	if (!$account->type) {
		$page = 'all.php';
	}

	chart_reload_or_redirect(sprintf('!acc/charts/accounts/%s?id=%d', $page, $account->id_chart));
}, $csrf_key);

$types_create = [
	Account::TYPE_EXPENSE => [
		'label' => Account::TYPES_NAMES[Account::TYPE_EXPENSE],
		'help' => 'Compte destiné à recevoir les dépenses (charges)',
	],
	Account::TYPE_REVENUE => [
		'label' => Account::TYPES_NAMES[Account::TYPE_REVENUE],
		'help' => 'Compte destiné à recevoir les recettes (produits)',
	],
	Account::TYPE_BANK => [
		'label' => Account::TYPES_NAMES[Account::TYPE_BANK],
		'help' => 'Compte bancaire, livret, ou intermédiaire financier (type HelloAsso, Paypal, Stripe, SumUp, etc.)',
	],
	Account::TYPE_CASH => [
		'label' => Account::TYPES_NAMES[Account::TYPE_CASH],
		'help' => 'Caisse qui sert aux espèces, par exemple la caisse de l\'atelier ou de la boutique.',
	],
	Account::TYPE_OUTSTANDING => [
		'label' => Account::TYPES_NAMES[Account::TYPE_OUTSTANDING],
		'help' => 'Paiements qui ont été reçus mais qui ne sont pas encore déposés sur un compte bancaire (typiquement les chèques reçus, qui seront déposés en banque plus tard).',
	],
	Account::TYPE_THIRD_PARTY => [
		'label' => Account::TYPES_NAMES[Account::TYPE_THIRD_PARTY],
		'help' => 'Fournisseur, membres de l\'association, collectivités ou services de l\'État par exemple.',
	],
	Account::TYPE_VOLUNTEERING_REVENUE => [
		'label' => 'Source du bénévolat',
		'help' => 'Pour indiquer d\'où provient le bénévolat (temps donné, prestation gratuite, etc.)',
	],
	Account::TYPE_VOLUNTEERING_EXPENSE => [
		'label' => 'Utilisation du bénévolat',
		'help' => 'Pour valoriser l\'utilisation du temps de bénévolat, les dons en nature, etc.',
	],
	Account::TYPE_NONE => [
		'label' => 'Autre type de compte',
	],
];

$ask = $from = $missing = $code_base = $code_value = null;

if ($id = (int)f('from')) {
	$from = $accounts->get($id);
	$code_base = $from->code;
	$code_value = $account->getNewNumberAvailable($code_base);
}
elseif ($id = (int)qg('ask')) {
	$ask = $accounts->get($id);
}

if ($account->type && !$from) {
	$code_base = $account->getNumberBase() ?? '';
	$code_value = $account->getNewNumberAvailable($code_base);

	if (null === f('from')) {
		$missing = $accounts->listMissing($account->type);
	}
}

$tpl->assign(compact('types_create', 'account', 'chart', 'ask', 'csrf_key', 'missing', 'code_base', 'code_value', 'from'));

$tpl->display('acc/charts/accounts/new.tpl');
