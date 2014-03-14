<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$error = false;
$parent = (int) utils::get('parent') ?: 0;

if (!empty($_POST['create']))
{
    if (!utils::CSRF_check('wiki_create'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $id = $wiki->create([
                'titre'         =>  utils::post('titre'),
                'parent'        =>  $parent,
            ]);

            utils::redirect('/admin/wiki/editer.php?id='.$id);
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