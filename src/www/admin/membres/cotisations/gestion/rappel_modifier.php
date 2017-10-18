<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

if (!qg('id') || !is_numeric(qg('id')))
{
    throw new UserException("Argument du numéro de rappel manquant.");
}

$rappels = new Rappels;

$rappel = $rappels->get(qg('id'));

if (!$rappel)
{
    throw new UserException("Ce rappel n'existe pas.");
}

$cotisations = new Cotisations;

if (f('save') && $form->check('edit_rappel_' . $rappel->id))
{
    try {
        if (f('delai_choix') == 0)
           $delai = 0;
        elseif (f('delai_choix') > 0)
            $delai = (int) f('delai_post');
        else
            $delai = -(int) f('delai_pre');

        $rappels->edit($rappel->id, [
            'sujet'         => f('sujet'),
            'texte'         => f('texte'),
            'delai'         => $delai,
            'id_cotisation' => f('id_cotisation'),
        ]);

        Utils::redirect('/admin/membres/cotisations/gestion/rappels.php');
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}

$rappel->delai_pre = 
    $rappel->delai_post = (abs($rappel->delai) ?: 30);

$rappel->delai_choix = ($rappel->delai == 0) ? 0 : ($rappel->delai > 0 ? 1 : -1);

$tpl->assign('rappel', $rappel);
$tpl->assign('cotisations', $cotisations->listCurrent());

$tpl->display('admin/membres/cotisations/gestion/rappel_modifier.tpl');
