<?php
namespace Garradin;

require_once __DIR__ . '/../../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (!Utils::get('id') || !is_numeric(Utils::get('id')))
{
    throw new UserException("Argument du numéro de rappel manquant.");
}

$rappels = new Rappels;

$rappel = $rappels->get(Utils::get('id'));

if (!$rappel)
{
    throw new UserException("Ce rappel n'existe pas.");
}

$cotisations = new Cotisations;

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('edit_rappel_' . $rappel['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            if (Utils::post('delai_choix') == 0)
               $delai = 0;
            elseif (Utils::post('delai_choix') > 0)
                $delai = (int) Utils::post('delai_post');
            else
                $delai = -(int) Utils::post('delai_pre');

            $rappels->edit($rappel['id'], [
                'sujet'		=>	Utils::post('sujet'),
                'texte'		=>	Utils::post('texte'),
                'delai'		=>	$delai,
                'id_cotisation'	=>	Utils::post('id_cotisation'),
            ]);

            Utils::redirect('/admin/membres/cotisations/gestion/rappels.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$rappel['delai_pre'] = $rappel['delai_post'] = abs($rappel['delai']) ?: 30;
$rappel['delai_choix'] = $rappel['delai'] == 0 ? 0 : ($rappel['delai'] > 0 ? 1 : -1);

$tpl->assign('rappel', $rappel);
$tpl->assign('cotisations', $cotisations->listCurrent());

$tpl->display('admin/membres/cotisations/gestion/rappel_modifier.tpl');

?>