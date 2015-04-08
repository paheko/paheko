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

if (Utils::post('delete'))
{
    if (!Utils::CSRF_check('wiki_files_'.$page['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $fichier = new Fichiers(Utils::post('delete'));
            
            if (!$fichier->checkAccess($user))
            {
                throw new UserException('Vous n\'avez pas accès à ce fichier.');
            }

            $fichier->remove();
            Utils::redirect('/admin/wiki/_fichiers.php?page=' . $page['id']);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

if (Utils::post('upload') || isset($_POST['uploadHelper_status']))
{
    if (!Utils::CSRF_check('wiki_files_'.$page['id']))
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
                echo json_encode([
                    'redirect'  =>  WWW_URL . $uri,
                    'callback'  =>  'insertHelper',
                    'file'      =>  [
                        'image' =>  (int)$fichier->image,
                        'id'    =>  (int)$fichier->id,
                        'nom'   =>  $fichier->nom,
                        'thumb' =>  $fichier->image ? $fichier->getURL(200) : false
                    ],
                ]);
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

$tpl->assign('fichiers', Fichiers::listLinkedFiles(Fichiers::LIEN_WIKI, $page['id'], false));
$tpl->assign('images', Fichiers::listLinkedFiles(Fichiers::LIEN_WIKI, $page['id'], true));

$tpl->assign('max_size', Utils::getMaxUploadSize());
$tpl->assign('error', $error);
$tpl->assign('page', $page);
$tpl->assign('sent', isset($_GET['sent']) ? true : false);

$tpl->assign('custom_js', ['upload_helper.min.js', 'wiki_fichiers.js']);

$tpl->assign('csrf_field_name', Utils::CSRF_field_name('wiki_files_' . $page['id']));
$tpl->assign('csrf_value', Utils::CSRF_create('wiki_files_' . $page['id']));

$tpl->display('admin/wiki/_fichiers.tpl');
