<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$error = false;

if (isset($_GET['ok']))
{
    $error = 'OK';
}

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('config_site'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $config->set('champs_obligatoires', utils::post('champs_obligatoires'));
            $config->set('champs_modifiables_membre', utils::post('champs_modifiables_membre'));
            $config->set('categorie_membres', utils::post('categorie_membres'));
            $config->save();

            utils::redirect('/admin/config/site.php?ok');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

if (utils::get('edit'))
{
    $source = Squelette::getSource(utils::get('edit'));

    if (!$source)
    {
        throw new UserException("Ce squelette n'existe pas.");
    }

    $csrf_key = 'edit_skel_'.md5(utils::get('edit'));

    if (utils::post('save'))
    {
        if (!utils::CSRF_check($csrf_key))
        {
            $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
        }
        else
        {
            if (Squelette::editSource(utils::get('edit'), utils::post('content')))
            {
                utils::redirect('/admin/config/site.php?edit='.rawurlencode(utils::get('edit')).'&ok');
            }
            else
            {
                $error = "Impossible d'enregistrer le squelette.";
            }
        }
    }

    $tpl->assign('edit', array('file' => trim(utils::get('edit')), 'content' => $source));
    $tpl->assign('csrf_key', $csrf_key);
    $tpl->assign('sources_json', json_encode(Squelette::listSources()));
}
else
{
    $tpl->assign('sources', Squelette::listSources());
}

$tpl->assign('error', $error);
$tpl->display('admin/config/site.tpl');

?>