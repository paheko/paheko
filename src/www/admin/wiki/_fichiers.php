<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

qv(['page' => 'required|numeric']);

$page = $wiki->getById(qg('page'));
$csrf_id = 'wiki_files_' . $page->id;

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
elseif (f('delete'))
{
    if ($form->check($csrf_id))
    {
        try {
            $fichier = new Fichiers(f('delete'));
            
            if (!$fichier->checkAccess($session))
            {
                throw new UserException('Vous n\'avez pas accès à ce fichier.');
            }

            $fichier->remove();
            Utils::redirect('/admin/wiki/_fichiers.php?page=' . $page->id);
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}
elseif (f('upload') || f('uploadHelper_mode'))
{
    $validate = ['fichier' => 'file|required'];

    if (f('uploadHelper_mode') == 'hash_only')
    {
        $validate = [
            'uploadHelper_fileHash' => 'required',
            'uploadHelper_fileName' => 'required',
        ];
    }

    $form->check($csrf_id, $validate);

    if (f('uploadHelper_status') > 0)
    {
        $form->addError('Un seul fichier peut être envoyé en même temps.');
    }
    
    if (!$form->hasErrors())
    {
        try {
            if (f('uploadHelper_mode') == 'hash_only' && f('uploadHelper_fileHash') && f('uploadHelper_fileName'))
            {
                $fichier = Fichiers::uploadExistingHash(f('uploadHelper_fileName'), f('uploadHelper_fileHash'));
            }
            else
            {
                $fichier = Fichiers::upload($_FILES['fichier']);
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
            $form->addError($e->getMessage());
        }
    }

    if (f('uploadHelper_mode') !== null)
    {
        echo json_encode(['error' => implode(PHP_EOL, $form->getErrorMessages())]);
        exit;
    }
}

$tpl->assign('fichiers', Fichiers::listLinkedFiles(Fichiers::LIEN_WIKI, $page->id, false));
$tpl->assign('images', Fichiers::listLinkedFiles(Fichiers::LIEN_WIKI, $page->id, true));

$tpl->assign('max_size', Utils::getMaxUploadSize());
$tpl->assign('page', $page);
$tpl->assign('sent', (bool)qg('sent'));
$tpl->assign('csrf_id', $csrf_id);

$tpl->assign('custom_js', ['upload_helper.min.js', 'wiki_fichiers.js']);

$tpl->display('admin/wiki/_fichiers.tpl');
