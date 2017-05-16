<?php

namespace Garradin;

use Garradin\Membres\Session;

require_once __DIR__ . '/_inc.php';

$errors = [];
$confirm = false;

if (f('confirm'))
{
    fc('edit_me_security', [
        'passe'       => 'confirmed',
        'passe_check' => 'required',
    ], $errors);

    if (f('passe_check') && !$session->checkPassword(f('passe_check')))
    {
        $errors[] = 'Le mot de passe fourni ne correspond pas au mot de passe actuel. Merci de bien vouloir renseigner votre mot de passe courant pour confirmer les changements.';
    }
    elseif (f('otp_secret') && !Session::checkOTP(f('otp_secret'), f('code')))
    {
        $errors[] = 'Le code TOTP entré n\'est pas valide.';
    }

    if (count($errors) === 0)
    {
        try {
            $data = [
                'clef_pgp' => f('clef_pgp'),
            ];

            if (f('passe') && !empty($config->get('champs_membres')->get('passe')->editable))
            {
                $data['passe'] = f('passe');
            }

            if (f('otp_secret'))
            {
                $data['secret_otp'] = f('otp_secret');
            }
            elseif (f('otp') == 'disable')
            {
                $data['secret_otp'] = null;
            }

            $session->editSecurity($data);
            Utils::redirect('/admin/');
        }
        catch (UserException $e)
        {
            $errors[] = $e->getMessage();
        }
    }

    $confirm = true;
}
elseif (f('save'))
{
    fc('edit_me_security', [
        'passe'       => 'confirmed',
    ], $errors);

    if (f('clef_pgp') && !$session->getPGPFingerprint(f('clef_pgp')))
    {
        $errors[] = 'Clé PGP invalide : impossible de récupérer l\'empreinte de la clé.';
    }
    
    if (count($errors) === 0)
    {
        $confirm = true;
    }
}

$tpl->assign('form_errors', $errors);
$tpl->assign('confirm', $confirm);

if (f('otp') == 'generate')
{
    $otp = $session->getNewOTPSecret();
    $tpl->assign('otp', $otp);
}
else
{
    $tpl->assign('otp', false);
}

$tpl->assign('pgp_disponible', \KD2\Security::canUseEncryption());

$fingerprint = '';

if ($user->clef_pgp)
{
    $fingerprint = $session->getPGPFingerprint($user->clef_pgp, true);
}

$tpl->assign('clef_pgp_fingerprint', $fingerprint);

$tpl->assign('passphrase', Utils::suggestPassword());
$tpl->assign('champs', $config->get('champs_membres')->getAll());

$tpl->assign('membre', $user);

$tpl->display('admin/mes_infos_securite.tpl');
