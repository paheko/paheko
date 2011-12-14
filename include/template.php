<?php

require_once GARRADIN_ROOT . '/include/template_lite/class.template.php';

$tpl = new Template_Lite;

$tpl->cache = false;

$tpl->compile_dir = GARRADIN_ROOT . '/cache/compiled';
$tpl->template_dir = GARRADIN_ROOT . '/templates';

$tpl->compile_check = true;

$tpl->reserved_template_varname = 'tpl';

$tpl->assign('www_url', WWW_URL);
$tpl->assign('self_url', utils::getSelfUrl());

$tpl->assign('is_logged', false);

if (class_exists('Garradin_Config'))
{
    $tpl->assign('config', Garradin_Config::getInstance()->getConfig());
}
else
{
    $tpl->assign('config', false);
}

function tpl_csrf_field($params)
{
    $name = utils::CSRF_field_name($params['key']);
    $value = utils::CSRF_create($params['key']);

    return '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
}

function tpl_form_field($params)
{
    $name = $params['name'];

    if (isset($_POST[$name]))
        $value = $_POST[$name];
    elseif (isset($params['data']) && isset($params['data'][$name]))
        $value = $params['data'][$name];
    elseif (isset($params['default']))
        $value = $params['default'];
    else
        $value = '';

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function tpl_format_tel($n)
{
    $n = preg_replace('![^\d\+]!', '', $n);
    $n = preg_replace('!(\+?\d{2})!', '\\1 ', $n);
    return $n;
}

function tpl_strftime_fr($ts, $format)
{
    return utils::strftime_fr($format, $ts);
}

function tpl_date_fr($ts, $format)
{
    return utils::date_fr($format, $ts);
}

function tpl_format_droits($params)
{
    $droits = $params['droits'];

    $out = array('connexion' => '', 'inscription' => '', 'membres' => '', 'compta' => '',
        'wiki' => '', 'config' => '');
    $classes = array(
        Garradin_Membres::DROIT_AUCUN   =>  'aucun',
        Garradin_Membres::DROIT_ACCES   =>  'acces',
        Garradin_Membres::DROIT_ECRITURE=>  'ecriture',
        Garradin_Membres::DROIT_ADMIN   =>  'admin',
    );

    foreach ($droits as $cle=>$droit)
    {
        $cle = str_replace('droit_', '', $cle);

        if (array_key_exists($cle, $out))
        {

            $class = $classes[$droit];
            $desc = false;
            $s = false;

            if ($cle == 'connexion')
            {
                if ($droit == Garradin_Membres::DROIT_AUCUN)
                    $desc = 'N\'a pas le droit de se connecter';
                else
                    $desc = 'Peut le droit de se connecter';
            }
            elseif ($cle == 'inscription')
            {
                if ($droit == Garradin_Membres::DROIT_AUCUN)
                    $desc = 'N\'a pas le droit de s\'inscrire seul';
                else
                    $desc = 'A le droit de s\'inscrire seul';
            }
            elseif ($cle == 'config')
            {
                $s = '&#x2611;';

                if ($droit == Garradin_Membres::DROIT_AUCUN)
                    $desc = 'Ne peut modifier la configuration';
                else
                    $desc = 'Peut modifier la configuration';
            }
            elseif ($cle == 'compta')
            {
                $s = '&euro;';
            }

            if (!$s)
                $s = strtoupper($cle[0]);

            if (!$desc)
            {
                $desc = ucfirst($cle). ' : ';

                if ($droit == Garradin_Membres::DROIT_AUCUN)
                    $desc .= 'Pas accès';
                elseif ($droit == Garradin_Membres::DROIT_ACCES)
                    $desc .= 'Lecture uniquement';
                elseif ($droit == Garradin_Membres::DROIT_ECRITURE)
                    $desc .= 'Lecture & écriture';
                else
                    $desc .= 'Administration';
            }

            $out[$cle] = '<b class="'.$class.' '.$cle.'" title="'
                .htmlspecialchars($desc, ENT_QUOTES, 'UTF-8').'">'.$s.'</b>';
        }
    }

    return implode(' ', $out);
}

$tpl->register_function('csrf_field', 'tpl_csrf_field');
$tpl->register_function('form_field', 'tpl_form_field');

$tpl->register_function('format_droits', 'tpl_format_droits');

$tpl->register_modifier('get_country_name', array('utils', 'getCountryName'));
$tpl->register_modifier('format_tel', 'tpl_format_tel');

$tpl->register_modifier('retard_cotisation', array('Garradin_Membres', 'checkCotisation'));

$tpl->register_modifier('strftime_fr', 'tpl_strftime_fr');
$tpl->register_modifier('date_fr', 'tpl_date_fr');

?>