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

// Vérification des hash avant upload
if ($hash_check = Utils::post('uploadHelper_hashCheck'))
{
    echo json_encode(Fichiers::checkHashList($hash_check));
    exit;
}

if (Utils::post('submit') || isset($_POST['uploadHelper_status']))
{
    if (!Utils::CSRF_check('wiki_upload_'.$page['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (Utils::post('uploadHelper_status') > 0)
    {
        $error = 'Un seul fichier peut être envoyé en même temps.';
    }
    elseif (!empty($_POST['fichier']) || isset($_FILES['fichier']))
    {
        try {
            if (isset($_POST['uploadHelper_status']) && !empty($_POST['fichier']))
            {
                $fichier = Fichiers::uploadExistingHash(Utils::post('fichier'), Utils::post('uploadHelper_fileHash'));
            }
            else
            {
                $fichier = Fichiers::upload($_FILES['fichier']);
            }

            // Lier le fichier à la page wiki
            $fichier->linkTo(Fichiers::LIEN_WIKI, $page['id']);
            $uri = '/admin/wiki/_fichiers.php?page=' . $page['id'] . '&sent';

            if (isset($_POST['uploadHelper_status']))
            {
                echo json_encode(['redirect' => WWW_URL . $uri]);
                exit;
            }

            Utils::redirect($uri);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
    else
    {
        $error = 'Aucun fichier envoyé.';
    }

    if (isset($_POST['uploadHelper_status']))
    {
        echo json_encode(['error' => $error]);
        exit;
    }
}

$tpl->assign('max_size', Utils::getMaxUploadSize());
$tpl->assign('error', $error);
$tpl->assign('page', $page);
$tpl->assign('sent', isset($_GET['sent']) ? true : false);

$tpl->assign('custom_js', ['upload_helper.min.js']);

$tpl->display('admin/wiki/_fichiers.tpl');
