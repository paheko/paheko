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

$tpl->register_function('csrf_field', 'tpl_csrf_field');
$tpl->register_function('form_field', 'tpl_form_field');

$tpl->register_modifier('get_country_name', array('utils', 'getCountryName'));
$tpl->register_modifier('format_tel', 'tpl_format_tel');

$tpl->register_modifier('retard_cotisation', array('Garradin_Membres', 'checkCotisation'));

$tpl->register_modifier('strftime_fr', 'tpl_strftime_fr');
$tpl->register_modifier('date_fr', 'tpl_date_fr');

?>