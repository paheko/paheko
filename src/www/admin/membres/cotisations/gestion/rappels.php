<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$rappels = new Rappels;
$cotisations = new Cotisations;

if (f('save') && $form->check('new_rappel'))
{
    try {
        if (f('delai_choix') == 0)
    	   $delai = 0;
        elseif (f('delai_choix') > 0)
            $delai = (int) f('delai_post');
        else
            $delai = -(int) f('delai_pre');

        $rappels->add([
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

$tpl->assign('liste', $rappels->listByCotisation());
$tpl->assign('cotisations', $cotisations->listCurrent());

$tpl->assign('default_subject', '[#NOM_ASSO] Échéance de cotisation');
$tpl->assign('default_text', "Bonjour #IDENTITE,\n\nVotre cotisation arrive à échéance dans #NB_JOURS jours.\n\n"
	.	"Merci de nous contacter pour renouveler votre cotisation.\n\nCordialement.\n\n"
    .   "--\n#NOM_ASSO\n#ADRESSE_ASSO\nE-Mail : #EMAIL_ASSO\nSite web : #SITE_ASSO");

$tpl->display('admin/membres/cotisations/gestion/rappels.tpl');
