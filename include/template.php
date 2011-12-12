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

$tpl->assign('config', Garradin_Config::getInstance()->getConfig());

function tpl_csrf_field($params)
{
    $name = utils::CSRF_field_name($params['key']);
    $value = utils::CSRF_create($params['key']);

    return '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
}

function tpl_form_field($params)
{
    $name = $params['name'];

    if (!empty($_POST[$name]))
        $value = $_POST[$name];
    elseif (!empty($params['default']) && !empty($params['default'][$name]))
        $value = $params['default'][$name];
    else
        $value = '';

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function has_right($right, $check)
{
    $right = constant('Garradin_Membres::DROIT_'.strtoupper($right));
    return array_key_exists($right, $check);
}

$tpl->register_function('csrf_field', 'tpl_csrf_field');
$tpl->register_function('form_field', 'tpl_form_field');

?>