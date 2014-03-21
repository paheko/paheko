<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$page = utils::get('_u') ?: 'index.php';

$plugin = new Plugin(utils::get('_p'));

define('Garradin\PLUGIN_ROOT', $plugin->path());
define('Garradin\PLUGIN_URL', WWW_URL . 'admin/plugin/' . $plugin->id() . '/');
define('Garradin\PLUGIN_QSP', '?');

$tpl->assign('plugin', $plugin->getInfos());
$tpl->assign('plugin_root', PLUGIN_ROOT);

$plugin->call('admin/' . $page);
