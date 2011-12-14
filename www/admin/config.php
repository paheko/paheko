<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['config'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('config'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            foreach ($config->getFieldsTypes() as $name=>$type)
            {
                $config->set($name, utils::post($name));
            }

            $config->save();

            utils::redirect('/admin/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

require_once GARRADIN_ROOT . '/include/class.membres_categories.php';

$cats = new Garradin_Membres_Categories;
$tpl->assign('membres_cats', $cats->listSimple());

$tpl->assign('champs_membres', $config->getChampsMembres());
$tpl->assign('error', $error);

$tpl->display('admin/config.tpl');

?>