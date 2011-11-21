<?php

require_once GARRADIN_ROOT . '/include/template_lite/class.template.php';

$tpl = new Template_Lite;

$tpl->cache = false;

$tpl->compile_dir = GARRADIN_ROOT . '/cache/compiled';
$tpl->template_dir = GARRADIN_ROOT . '/templates';

$tpl->compile_check = true;

$tpl->reserved_template_varname = 'smarty';

$tpl->assign('www_url', WWW_URL);
$tpl->assign('self_url', utils::getSelfUrl());

$tpl->assign('is_logged', false);

?>