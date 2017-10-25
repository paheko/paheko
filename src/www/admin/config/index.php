<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$couleur1 = '#9c4f15';
$couleur2 = '#d98628';

if (f('save') && $form->check('config'))
{
    try {
        $config->set('nom_asso', f('nom_asso'));
        $config->set('email_asso', f('email_asso'));
        $config->set('adresse_asso', f('adresse_asso'));
        $config->set('site_asso', f('site_asso'));
        $config->set('email_envoi_automatique', f('email_envoi_automatique'));
        $config->set('accueil_wiki', f('accueil_wiki'));
        $config->set('accueil_connexion', f('accueil_connexion'));
        $config->set('categorie_membres', f('categorie_membres'));
        
        $config->set('champ_identite', f('champ_identite'));
        $config->set('champ_identifiant', f('champ_identifiant'));

        $config->set('pays', f('pays'));
        $config->set('monnaie', f('monnaie'));

        // N'enregistrer les couleurs que si ce ne sont pas les couleurs par défaut
        if (f('couleur1') != $couleur1 || f('couleur2') != $couleur2)
        {
            $config->set('couleur1', f('couleur1'));
            $config->set('couleur2', f('couleur2'));

            if (f('image_fond'))
            {
                $config->set('image_fond', f('image_fond'));
            }
        }
        else
        {
            $config->set('couleur1', null);
            $config->set('couleur2', null);
            $config->set('image_fond', null);
        }

        $config->save();

        Utils::redirect('/admin/config/?ok');
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}

$tpl->assign('ok', qg('ok') !== null);

$server_time = time();

$tpl->assign('garradin_version', garradin_version() . ' [' . (garradin_manifest() ?: 'release') . ']');
$tpl->assign('php_version', phpversion());
$tpl->assign('has_gpg_support', \KD2\Security::canUseEncryption());
$tpl->assign('server_time', $server_time);

$v = \SQLite3::version();
$tpl->assign('sqlite_version', $v['versionString']);

$tpl->assign('pays', Utils::getCountryList());

$cats = new Membres\Categories;
$tpl->assign('membres_cats', $cats->listSimple());

$tpl->assign('champs', $config->get('champs_membres')->getList());

$tpl->assign('couleur1', $config->get('couleur1') ?: $couleur1);
$tpl->assign('couleur2', $config->get('couleur2') ?: $couleur2);
$tpl->assign('couleurs_defaut', [$couleur1, $couleur2]);

$tpl->assign('custom_js', ['color_helper.js']);
$tpl->display('admin/config/index.tpl');
