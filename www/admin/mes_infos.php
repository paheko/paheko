<?php

require_once __DIR__ . '/_inc.php';

if (count($config->get('champs_modifiables_membre')) == 0)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$membre = $membres->getLoggedUser();

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('edit_me'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (utils::post('passe') != utils::post('repasse'))
    {
        $error = 'La vérification ne correspond pas au mot de passe.';
    }
    else
    {
        try {
            $data = array();

            foreach ($config->get('champs_modifiables_membre') as $champ)
            {
                $data[$champ] = utils::post($champ);
            }

            if (utils::post('passe') == '')
            {
                unset($data['passe']);
            }

            $data['lettre_infos'] = utils::post('lettre_infos');

            $membres->edit($membre['id'], $data);
            $membres->updateSessionData();

            utils::redirect('/admin/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('passphrase', utils::suggestPassword());
$tpl->assign('obligatoires', $config->get('champs_obligatoires'));

$tpl->assign('pays', utils::getCountryList());
$tpl->assign('current_cc', utils::post('pays') ?: $membre['pays']);

$tpl->assign('membre', $membre);

$tpl->display('admin/mes_infos.tpl');

?>