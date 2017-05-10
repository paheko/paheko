<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$error = false;

if (isset($_GET['ok']))
{
    $error = 'OK';
}

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('config'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $config->set('nom_asso', Utils::post('nom_asso'));
            $config->set('email_asso', Utils::post('email_asso'));
            $config->set('adresse_asso', Utils::post('adresse_asso'));
            $config->set('site_asso', Utils::post('site_asso'));
            $config->set('email_envoi_automatique', Utils::post('email_envoi_automatique'));
            $config->set('accueil_wiki', Utils::post('accueil_wiki'));
            $config->set('accueil_connexion', Utils::post('accueil_connexion'));
            $config->set('categorie_membres', Utils::post('categorie_membres'));
            
            $config->set('champ_identite', Utils::post('champ_identite'));
            $config->set('champ_identifiant', Utils::post('champ_identifiant'));

            $config->set('pays', Utils::post('pays'));
            $config->set('monnaie', Utils::post('monnaie'));

            $config->save();

            Utils::redirect('/admin/config/?ok');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$server_time = time();
$ntp_time = \KD2\Security_OTP::getTimeFromNTP(NTP_SERVER);
$diff = $server_time - $ntp_time;

$tpl->assign('garradin_version', garradin_version() . ' [' . (garradin_manifest() ?: 'release') . ']');
$tpl->assign('php_version', phpversion());
$tpl->assign('has_gpg_support', \KD2\Security::canUseEncryption());
$tpl->assign('server_time', $server_time);
$tpl->assign('time_diff', $diff);

$v = \SQLite3::version();
$tpl->assign('sqlite_version', $v['versionString']);

$tpl->assign('pays', Utils::getCountryList());

$cats = new Membres\Categories;
$tpl->assign('membres_cats', $cats->listSimple());

$tpl->assign('champs', $config->get('champs_membres')->getList(true));

$tpl->display('admin/config/index.tpl');
