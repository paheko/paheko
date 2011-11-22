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

function tpl_csrf_field($params)
{
    $name = utils::CSRF_field_name($params['key']);
    $value = utils::CSRF_create($params['key']);

    return '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
}

$tpl->register_function('csrf_field', 'tpl_csrf_field');

?>