<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$error = false;
$parent = (int) Utils::get('parent') ?: 0;

if (!empty($_POST['create']))
{
    if (!Utils::CSRF_check('wiki_create'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $id = $wiki->create([
                'titre'         =>  Utils::post('titre'),
                'parent'        =>  $parent,
            ]);

            Utils::redirect('/admin/wiki/editer.php?id='.$id);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->display('admin/wiki/creer.tpl');

?>