<?php
namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Entities\Accounting\Line;
use Garradin\Accounting\Accounts;
use Garradin\Accounting\Charts;
use Garradin\Users\Session;

require_once __DIR__ . '/../../_inc.php';

$session = Session::getInstance();
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
$account->position = Account::ASSET_OR_LIABILITY;

$types = $account::TYPES_NAMES;
$types[0] = '-- Pas un compte usuel';

$type = f('type') ?? qg('type');

// Simple creation with pre-determined account type
if ($type !== null) {
	$account->type = (int)$type;
	$account->position = Accounts::getPositionFromType($account->type);
	$account->code = $accounts->getNextCodeForType($account->type);
}

$form->runIf('save', function () use ($account, $accounts, $chart, $current_year) {
	$db = DB::getInstance();

	$db->begin();
	$account->importForm();

	$account->id_chart = $chart->id();
	$account->user = 1;
	$account->save();

	if (!empty(f('opening_amount')) && $current_year) {
		$t = new Transaction;
		$t->label = 'Solde d\'ouverture du compte';
		$t->id_creator = Session::getUserId();
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

	$url = sprintf('!acc/charts/accounts/%s?id=%d', $page, $account->id_chart);
	Utils::redirect($url);
}, 'acc_accounts_new');

$types_create = [
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
	Account::TYPE_EXPENSE => [
		'label' => Account::TYPES_NAMES[Account::TYPE_EXPENSE],
		'help' => 'Compte destiné à recevoir les dépenses (charges)',
	],
	Account::TYPE_REVENUE => [
		'label' => Account::TYPES_NAMES[Account::TYPE_REVENUE],
		'help' => 'Compte destiné à recevoir les recettes (produits)',
	],
	Account::TYPE_ANALYTICAL => [
		'label' => Account::TYPES_NAMES[Account::TYPE_ANALYTICAL],
		'help' => 'Permet de suivre un budget spécifique, un projet, par exemple : bourse aux vélos, séjour au ski, etc.',
	],
	Account::TYPE_VOLUNTEERING => [
		'label' => 'Projet analytique',
		'help' => 'Pour valoriser le temps de bénévolat, les dons en nature, etc.',
	],
	Account::TYPE_NONE => [
		'label' => 'Autre type de compte',
	],
];

$type = $account->type;

$tpl->assign(compact('types', 'types_create', 'account', 'chart'));

$tpl->display('acc/charts/accounts/new.tpl');
