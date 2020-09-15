<?php
namespace Garradin;

use Garradin\Entities\Accounting\Transaction;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ECRITURE);

$chart = $year->chart();
$accounts = $chart->accounts();

$transaction = new Transaction;
$lines = [[], []];

if (f('save') && $form->check('acc_transaction_new')) {
    try {
        // Advanced transaction: handle lines
        if (f('type') == 'advanced' && $lines = f('lines'))
        {
            $max = count($lines['label']);

            if ($max != count($lines['debit'])
                || $max != count($lines['credit'])
                || $max != count($lines['reference'])
                || $max != count($lines['account']))
            {
                throw new UserException('Erreur dans les lignes de l\'écriture');
            }

            $out = [];

            // Reorder the POST data as a proper array
            for ($i = 0; $i < $max; $i++) {
                $out[] = [
                    'debit'      => $lines['debit'][$i],
                    'credit'     => $lines['credit'][$i],
                    'reference'  => $lines['reference'][$i],
                    'label'      => $lines['label'][$i],
                    'account'    => $lines['account'][$i],
                ];
            }

            $_POST['lines'] = $lines = $out;
        }

        $transaction->id_year = $year->id();
        $transaction->importFromSimpleForm($chart->id());
        $transaction->save();

        // Append file
        if (!empty($_FILES['file']['name'])) {
            $file = Fichiers::upload($_FILES['file']);
            $file->linkTo(Fichiers::LIEN_COMPTA, $transaction->id());
        }

        Utils::redirect(Utils::getSelfURL(false) . '?ok=' . $transaction->id());
    }
    catch (UserException $e) {
        $form->addError($e->getMessage());
    }
}

$tpl->assign('date', $session->get('context_compta_date') ?: false);
$tpl->assign('ok', (int) qg('ok'));

$tpl->assign('lines', $lines);

$tpl->assign('analytical_accounts', ['' => '-- Aucun'] + $accounts->listAnalytical());
$tpl->display('acc/transactions/new.tpl');
