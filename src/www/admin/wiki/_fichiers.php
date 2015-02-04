<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if ((trim(Utils::get('page')) == '') || !is_numeric(Utils::get('page')))
{
    throw new UserException('Numéro de page invalide.');
}

$page = $wiki->getById(Utils::get('page'));
$error = false;

if (!$page)
{
    throw new UserException('Page introuvable.');
}

if (Utils::post('submit'))
{
    if (!Utils::CSRF_check('file_upload_'.$page['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $fichier = Fichiers::upload($_FILES['fichier'], Utils::post('titre'));
            $fichier->link(Fichiers::LIEN_WIKI, $page['id']);

            Utils::redirect('/admin/wiki/_fichiers.php?ok');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('max_size', Utils::getMaxUploadSize());
$tpl->assign('error', $error);
$tpl->assign('sent', isset($_GET['sent']) ? true : false);

$tpl->assign('custom_js', ['file_upload.js']);

$tpl->display('admin/wiki/_fichiers.tpl');
