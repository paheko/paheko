<?php

namespace Garradin;

use Garradin\Membres\Session;

require_once __DIR__ . '/_inc.php';

$confirm = false;

if (f('confirm'))
{
    $form->check('edit_me_security', [
        'passe'       => 'confirmed|min:6',
        'passe_check' => 'required',
    ]);

    if (f('passe_check') && !$session->checkPassword(f('passe_check')))
    {
        $form->addError('Le mot de passe fourni ne correspond pas au mot de passe actuel. Merci de bien vouloir renseigner votre mot de passe courant pour confirmer les changements.');
    }
    elseif (f('otp_secret') && !Session::checkOTP(f('otp_secret'), f('code')))
    {
        $form->addError('Le code TOTP entré n\'est pas valide.');
    }

    if (!$form->hasErrors())
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
            $form->addError($e->getMessage());
        }
    }

    $confirm = true;
}
elseif (f('save'))
{
    $form->check('edit_me_security', [
        'passe'       => 'confirmed|min:6',
    ]);

    if (f('clef_pgp') && !$session->getPGPFingerprint(f('clef_pgp')))
    {
        $form->addError('Clé PGP invalide : impossible de récupérer l\'empreinte de la clé.');
    }
    
    if (!$form->hasErrors())
    {
        $confirm = true;
    }
}

$tpl->assign('confirm', $confirm);

if (f('otp') == 'generate')
{
    $otp = $session->getNewOTPSecret();
    $tpl->assign('otp', $otp);
}
elseif (f('otp_secret'))
{
    $tpl->assign('otp', f('otp_secret'));
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
