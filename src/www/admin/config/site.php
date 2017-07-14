<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('config_site'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $config->set('champs_obligatoires', Utils::post('champs_obligatoires'));
            $config->set('champs_modifiables_membre', Utils::post('champs_modifiables_membre'));
            $config->set('categorie_membres', Utils::post('categorie_membres'));
            $config->save();

            Utils::redirect('/admin/config/site.php?ok');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

if (Utils::post('select') && Utils::post('reset'))
{
    if (!Utils::CSRF_check('squelettes'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            foreach (Utils::post('select') as $source)
            {
                if (!Squelette::resetSource($source))
                {
                    throw new UserException('Impossible de réinitialiser le squelette.');
                }
            }
        
            Utils::redirect('/admin/config/site.php?reset_ok');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}


if (qg('edit'))
{
    $source = Squelette::getSource(qg('edit'));

    if (!$source)
    {
        throw new UserException("Ce squelette n'existe pas.");
    }

    $csrf_key = 'edit_skel_'.md5(qg('edit'));

    if (Utils::post('save'))
    {
        if (!Utils::CSRF_check($csrf_key))
        {
            $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
        }
        else
        {
            if (Squelette::editSource(qg('edit'), Utils::post('content')))
            {
                $fullscreen = null !== qg('fullscreen') ? '#fullscreen' : '';
                Utils::redirect('/admin/config/site.php?edit='.rawurlencode(qg('edit')).'&ok'.$fullscreen);
            }
            else
            {
                $error = "Impossible d'enregistrer le squelette.";
            }
        }
    }

    $tpl->assign('edit', ['file' => trim(qg('edit')), 'content' => $source]);
    $tpl->assign('csrf_key', $csrf_key);
}

$tpl->assign('sources', Squelette::listSources());

$tpl->assign('reset_ok', qg('reset_ok'));
$tpl->assign('error', $error);
$tpl->display('admin/config/site.tpl');
