<?php
namespace Garradin;

use KD2\ErrorManager;

require_once __DIR__ . '/_inc.php';

if (qg('type') == 'errors' && ENABLE_TECH_DETAILS)
{
    $reports = ErrorManager::getReportsFromLog(null, qg('id'));

    $reports = array_reverse($reports, true);

    foreach ($reports as &$report)
    {
        $report->context->date = strtotime($report->context->date);
    }

    unset($report);

    $errors = [];

    if (qg('id'))
    {
        if (!count($reports)) {
            throw new UserException('Erreur inconnue');
        }

        $tpl->assign('id', qg('id'));
        $tpl->assign('main', reset($reports));
        $tpl->assign('reports', $reports);
    }
    else
    {
        foreach ($reports as $report)
        {
            if (!isset($errors[$report->context->id]))
            {
                $errors[$report->context->id] = [
                    'message' => $report->errors[0]->message,
                    'source' => sprintf('%s:%d', $report->errors[0]->backtrace[0]->file, $report->errors[0]->backtrace[0]->line),
                    'count' => 0,
                ];
            }

            $errors[$report->context->id]['last_seen'] = $report->context->date;
            $errors[$report->context->id]['count']++;
        }

        $tpl->assign('errors', $errors);
    }
}

$tpl->assign('type', qg('type'));
$tpl->display('admin/config/logs.tpl');
