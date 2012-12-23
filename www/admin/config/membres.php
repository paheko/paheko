<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['config'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$error = false;

// Il est nécessaire de créer une nouvelle instance ici, sinon
// l'enregistrement des modifs ne marchera pas car les deux instances seront identiques.
// Càd si on utilise directement l'instance de $config, elle sera modifiée directement
// du coup quand on essaiera de comparer si ça a changé ça comparera deux fois la même chose
// donc ça n'aura pas changé forcément.
$champs = new Champs_Membres($config->get('champs_membres'));

if (isset($_GET['ok']))
{
    $error = 'OK';
}

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('config_membres'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $champs->setAll(utils::post('champs'));
            $champs->save();

            utils::redirect('/admin/config/membres.php?ok');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

function tpl_get_type($type)
{
    global $types;
    return $types[$type];
}

$tpl->assign('error', $error);

$types = $champs->getTypes();

$tpl->assign('champs', utils::post('champs') ?: $config->get('champs_membres')->getAll());
$tpl->assign('types', $types);
$tpl->assign('new', utils::post('new'));

$tpl->register_modifier('get_type', 'Garradin\tpl_get_type');
$tpl->display('admin/config/membres.tpl');

?>