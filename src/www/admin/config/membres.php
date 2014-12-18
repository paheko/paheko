<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$error = false;

// Restauration de ce qui était en session
if ($champs = $membres->sessionGet('champs_membres'))
{
    $champs = new Membres\Champs($champs);
}
else
{
    // Il est nécessaire de créer une nouvelle instance ici, sinon
    // l'enregistrement des modifs ne marchera pas car les deux instances seront identiques.
    // Càd si on utilise directement l'instance de $config, elle sera modifiée directement
    // du coup quand on essaiera de comparer si ça a changé ça comparera deux fois la même chose
    // donc ça n'aura pas changé forcément.
    $champs = new Membres\Champs($config->get('champs_membres'));
}

if (isset($_GET['ok']))
{
    $error = 'OK';
}

if (!empty($_POST['save']) || !empty($_POST['add']) || !empty($_POST['review']) || !empty($_POST['reset']))
{
    if (!Utils::CSRF_check('config_membres'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        if (!empty($_POST['reset']))
        {
            $membres->sessionStore('champs_membres', null);
            Utils::redirect('/admin/config/membres.php');
        }
        elseif (!empty($_POST['review']))
        {
            try {
                $nouveau_champs = Utils::post('champs');

                foreach ($nouveau_champs as $key=>&$cfg)
                {
                    $cfg['type'] = $champs->get($key, 'type');
                }
                
                $champs->setAll($nouveau_champs);
                $membres->sessionStore('champs_membres', (string)$champs);

                Utils::redirect('/admin/config/membres.php?review');
            }
            catch (UserException $e)
            {
                $error = $e->getMessage();
            }
        }
        elseif (!empty($_POST['add']))
        {
            try {
                if (Utils::post('preset'))
                {
                    $presets = Membres\Champs::listUnusedPresets($champs);
                    if (!array_key_exists(Utils::post('preset'), $presets))
                    {
                        throw new UserException('Le champ pré-défini demandé ne fait pas partie des champs disponibles.');
                    }

                    $champs->add(Utils::post('preset'), $presets[Utils::post('preset')]);
                }
                elseif (Utils::post('new'))
                {
                    $presets = Membres\Champs::importPresets();
                    $new = Utils::post('new');

                    if (array_key_exists($new, $presets))
                    {
                        throw new UserException('Le champ personnalisé ne peut avoir le même nom qu\'un champ pré-défini.');
                    }

                    $config = [
                        'type'  =>  Utils::post('new_type'),
                        'title' =>  Utils::post('new_title'),
                        'editable'  =>  true,
                        'mandatory' =>  false,
                    ];

                    if ($config['type'] == 'select' || $config['type'] == 'multiple')
                    {
                        $config['options'] = ['Première option'];
                    }

                    $champs->add($new, $config);
                }

                $membres->sessionStore('champs_membres', (string) $champs);

                Utils::redirect('/admin/config/membres.php?added');
            }
            catch (UserException $e)
            {
                $error = $e->getMessage();
            }
        }
        elseif (!empty($_POST['save']))
        {
            try {
                $champs->save();
                $membres->sessionStore('champs_membres', null);
                Utils::redirect('/admin/config/membres.php?ok');
            }
            catch (UserException $e)
            {
                $error = $e->getMessage();
            }
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('review', isset($_GET['review']) ? true : false);

$types = $champs->getTypes();

$tpl->assign('champs', $champs->getAll());
$tpl->assign('types', $types);
$tpl->assign('presets', Membres\Champs::listUnusedPresets($champs));
$tpl->assign('new', Utils::post('new'));

$tpl->register_modifier('get_type', function ($type) use ($types) {
    return $types[$type];
});

$tpl->assign('csrf_name', Utils::CSRF_field_name('config_membres'));
$tpl->assign('csrf_value', Utils::CSRF_create('config_membres'));

$tpl->display('admin/config/membres.tpl');

?>