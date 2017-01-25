<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$membre = $membres->getLoggedUser();

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('edit_me'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (Utils::post('passe') != Utils::post('repasse'))
    {
        $error = 'La vérification ne correspond pas au mot de passe.';
    }
    else
    {
        try {
            $data = [];

            foreach ($config->get('champs_membres')->getAll() as $key=>$c)
            {
                if (!empty($c['editable']))
                {
                    $data[$key] = Utils::post($key);
                }
            }

            $membres->edit($membre['id'], $data, false);
            $membres->updateSessionData();

            if (Utils::post('otp') == 'generate')
            {
                $secret = $membres->setOTP();
                Utils::redirect('/admin/mes_infos.php?otp=' . rawurlencode($secret));
            }
            elseif (Utils::post('otp') == 'disable')
            {
                $secret = $membres->disableOTP();
                Utils::redirect('/admin/mes_infos.php?otp=off');
            }
            else
            {
                Utils::redirect('/admin/');
            }
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('otp_status', Utils::get('otp'));

if (Utils::get('otp') && Utils::get('otp') != 'off')
{
    $url = \KD2\Security_OTP::getOTPAuthURL($config->get('nom_asso'), Utils::get('otp'));
    $qrcode = new \KD2\QRCode($url);
    $qrcode = 'data:image/svg+xml;base64,' . base64_encode($qrcode->toSVG());
    $tpl->assign('otp_qrcode', $qrcode);

    $tpl->assign('otp_status', implode(' ', str_split(Utils::get('otp'), 4)));
}

$tpl->assign('passphrase', Utils::suggestPassword());
$tpl->assign('champs', $config->get('champs_membres')->getAll());

$tpl->assign('membre', $membre);

$tpl->display('admin/mes_infos.tpl');
