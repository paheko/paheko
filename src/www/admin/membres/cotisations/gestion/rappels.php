<?php
namespace Garradin;

require_once __DIR__ . '/../../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$rappels = new Rappels;
$cotisations = new Cotisations;

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('new_rappel'))
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

            $rappels->add([
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

$tpl->assign('liste', $rappels->listByCotisation());
$tpl->assign('cotisations', $cotisations->listCurrent());

$tpl->assign('default_subject', '[#NOM_ASSO] Échéance de cotisation');
$tpl->assign('default_text', "Bonjour #IDENTITE,\n\nVotre cotisation arrive à échéance dans #NB_JOURS jours.\n\n"
	.	"Merci de nous contacter pour renouveler votre cotisation.\n\nCordialement.\n\n"
    .   "--\n#NOM_ASSO\n#ADRESSE_ASSO\nE-Mail : #EMAIL_ASSO\nSite web : #SITE_ASSO");

$tpl->display('admin/membres/cotisations/gestion/rappels.tpl');

?>