<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

qv(['page' => 'required|numeric']);

$page = $wiki->getById(qg('page'));
$form_errors = [];

if (!$page)
{
    throw new UserException('Page introuvable.');
}

// Vérification des hash avant upload
if ($hash_check = f('uploadHelper_hashCheck'))
{
    echo json_encode(Fichiers::checkHashList($hash_check));
    exit;
}

if (f('delete'))
{
    if (fc('wiki_files_'.$page->id, [], $form_errors))
    {
        try {
            $fichier = new Fichiers(f('delete'));
            
            if (!$fichier->checkAccess($user))
            {
                throw new UserException('Vous n\'avez pas accès à ce fichier.');
            }

            $fichier->remove();
            Utils::redirect('/admin/wiki/_fichiers.php?page=' . $page->id);
        }
        catch (UserException $e)
        {
            $form_errors[] = $e->getMessage();
        }
    }
}

if (f('upload') || f('uploadHelper_status') !== null)
{
    fc('wiki_files_'.$page->id, [], $form_errors);

    if (f('uploadHelper_status') > 0)
    {
        $form_errors[] = 'Un seul fichier peut être envoyé en même temps.';
    }
    
    if (f('fichier') && count($form_errors) === 0)
    {
        try {
            if (null !== f('uploadHelper_status') && f('fichier'))
            {
                $fichier = Fichiers::uploadExistingHash(f('fichier'), f('uploadHelper_fileHash'));
            }
            else
            {
                $fichier = Fichiers::upload(f('fichier'));
            }

            // Lier le fichier à la page wiki
            $fichier->linkTo(Fichiers::LIEN_WIKI, $page->id);
            $uri = '/admin/wiki/_fichiers.php?page=' . $page->id . '&sent';

            if (f('uploadHelper_status') !== null)
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
            $form_errors[] = $e->getMessage();
        }
    }
    else
    {
        $form_errors[] = 'Aucun fichier envoyé.';
    }

    if (f('uploadHelper_status') !== null)
    {
        echo json_encode(['error' => implode(PHP_EOL, $form_errors)]);
        exit;
    }
}

$tpl->assign('fichiers', Fichiers::listLinkedFiles(Fichiers::LIEN_WIKI, $page->id, false));
$tpl->assign('images', Fichiers::listLinkedFiles(Fichiers::LIEN_WIKI, $page->id, true));

$tpl->assign('max_size', Utils::getMaxUploadSize());
$tpl->assign('form_errors', $form_errors);
$tpl->assign('page', $page);
$tpl->assign('sent', (bool)qg('sent'));

$tpl->assign('custom_js', ['upload_helper.min.js', 'wiki_fichiers.js']);

$tpl->assign('csrf_field_name', Utils::CSRF_field_name('wiki_files_' . $page->id));
$tpl->assign('csrf_value', Utils::CSRF_create('wiki_files_' . $page->id));

$tpl->display('admin/wiki/_fichiers.tpl');
