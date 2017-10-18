<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if (f('save') && $form->check('config_site'))
{
    try {
        $config->set('champs_obligatoires', f('champs_obligatoires'));
        $config->set('champs_modifiables_membre', f('champs_modifiables_membre'));
        $config->set('categorie_membres', f('categorie_membres'));
        $config->save();

        Utils::redirect('/admin/config/site.php?ok');
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}

if (f('select') && f('reset') && $form->check('squelettes'))
{
    try {
        foreach (f('select') as $source)
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
        $form->addError($e->getMessage());
    }
}


if (qg('edit'))
{
    $source = Squelette::getSource(qg('edit'));

    if (!$source)
    {
        throw new UserException("Ce squelette n'existe pas.");
    }

    $csrf_key = 'edit_skel_' . md5(qg('edit'));

    if (f('save') && $form->check($csrf_key))
    {
        if (Squelette::editSource(qg('edit'), f('content')))
        {
            $fullscreen = null !== qg('fullscreen') ? '#fullscreen' : '';
            Utils::redirect('/admin/config/site.php?edit='.rawurlencode(qg('edit')).'&ok'.$fullscreen);
        }
        else
        {
            $form->addError("Impossible d'enregistrer le squelette.");
        }
    }

    $tpl->assign('edit', ['file' => trim(qg('edit')), 'content' => $source]);
    $tpl->assign('csrf_key', $csrf_key);
}

$tpl->assign('sources', Squelette::listSources());

$tpl->assign('reset_ok', qg('reset_ok') !== null);
$tpl->assign('ok', qg('ok') !== null);
$tpl->display('admin/config/site.tpl');
