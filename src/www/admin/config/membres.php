<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$membres = new Membres;

// Restauration de ce qui était 
if ($champs = f('champs'))
{
    if (is_string($champs))
    {
        $champs = json_decode($champs, true);
    }

    try {
        $champs = new Membres\Champs($champs);
    }
    catch (UserException $e)
    {
        $champs = new Membres\Champs($config->get('champs_membres'));
        unset($_POST['review']);
        $form->addError($e->getMessage());
    }
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

if (f('save') || f('add') || f('review') || f('reset'))
{
    $form->check('config_membres');

    if (!$form->hasErrors())
    {
        if (f('reset'))
        {
            Utils::redirect('/admin/config/membres.php');
        }
        elseif (f('add'))
        {
            try {
                if (f('preset'))
                {
                    $presets = Membres\Champs::listUnusedPresets($champs);
                    if (!array_key_exists(f('preset'), $presets))
                    {
                        throw new UserException('Le champ pré-défini demandé ne fait pas partie des champs disponibles.');
                    }

                    $champs->add(f('preset'), $presets[f('preset')]);
                }
                elseif (f('new'))
                {
                    $presets = Membres\Champs::importPresets();
                    $new = f('new');

                    if (array_key_exists($new, $presets))
                    {
                        throw new UserException('Le champ personnalisé ne peut avoir le même nom qu\'un champ pré-défini.');
                    }

                    $config = [
                        'type'  =>  f('new_type'),
                        'title' =>  f('new_title'),
                        'editable'  =>  true,
                        'mandatory' =>  false,
                    ];

                    if ($config['type'] == 'select' || $config['type'] == 'multiple')
                    {
                        $config['options'] = ['Première option'];
                    }

                    $champs->add($new, $config);
                }

                $tpl->assign('status', 'ADDED');
            }
            catch (UserException $e)
            {
                $form->addError($e->getMessage());
            }
        }
        elseif (f('save'))
        {
            try {
                $champs->save();
                Utils::redirect('/admin/config/membres.php?ok');
            }
            catch (UserException $e)
            {
                $form->addError($e->getMessage());
            }
        }
    }
}
else
{
    $tpl->assign('status', null !== qg('ok'));
}

$tpl->assign('review', (bool) f('review'));

$types = $champs->getTypes();

$tpl->assign('champs', $champs->getAll());
$tpl->assign('types', $types);
$tpl->assign('presets', Membres\Champs::listUnusedPresets($champs));
$tpl->assign('new', f('new'));

$tpl->register_modifier('get_type', function ($type) use ($types) {
    return $types[$type];
});

$tpl->assign('title', 'Configuration — ' . (null !== qg('review') ? 'Confirmer les changements' : 'Fiche membres'));

$tpl->display('admin/config/membres.tpl');
