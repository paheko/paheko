<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$membre = $membres->getLoggedUser();

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

$error = false;
$confirm = false;

if (!empty($_POST['confirm']))
{
    if (!Utils::CSRF_check('edit_me_security'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (trim(Utils::post('passe_confirm')) === '')
    {
        $error = 'Merci de bien vouloir renseigner votre mot de passe courant pour confirmer les changements.';
    }
    elseif ($membres->checkPassword(Utils::post('passe_confirm')))
    {
        $error = 'Le mot de passe fourni ne correspond pas au mot de passe actuel. Merci de bien vouloir renseigner votre mot de passe courant pour confirmer les changements.';
    }
    elseif (Utils::post('passe') != Utils::post('repasse'))
    {
        $error = 'La vérification ne correspond pas au mot de passe.';
    }
    elseif (Utils::post('otp_secret') && !$membres->checkOTP(Utils::post('otp_secret'), Utils::post('code')))
    {
        $error = 'Le code TOTP entré n\'est pas valide.';
    }
    else
    {
        try {
            $data = [
                'clef_pgp' => Utils::post('clef_pgp'),
            ];

            if (Utils::post('passe') && !empty($config->get('champs_membres')->get('passe')['editable']))
            {
                $data['passe'] = Utils::post('passe');
            }

            if (Utils::post('otp_secret'))
            {
                $data['secret_otp'] = Utils::post('otp_secret');
            }
            elseif (Utils::post('otp') == 'disable')
            {
                $data['secret_otp'] = null;
            }

            $membres->editSecurity($data);
            Utils::redirect('/admin/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }

    $confirm = true;
}
elseif (Utils::post('save'))
{
    if (!Utils::CSRF_check('edit_me_security'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (Utils::post('passe') != Utils::post('repasse'))
    {
        $error = 'La vérification ne correspond pas au mot de passe.';
    }
    else
    {
        $confirm = true;
    }
}

$tpl->assign('error', $error);
$tpl->assign('confirm', $confirm);

if (Utils::post('otp') == 'generate')
{
    $otp = $membres->getNewOTPSecret();
    $tpl->assign('otp', $otp);
}

$tpl->assign('pgp_disponible', \KD2\Security::canUseEncryption());
$tpl->assign('clef_pgp_fingerprint', !empty($membre['clef_pgp']) ? \KD2\Security::getEncryptionKeyFingerprint($membre['clef_pgp']) : null);

$tpl->assign('passphrase', Utils::suggestPassword());
$tpl->assign('champs', $config->get('champs_membres')->getAll());

$tpl->assign('membre', $membre);

$tpl->display('admin/mes_infos_securite.tpl');
