<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$page = !empty($_GET['_u']) ? $_GET['_u'] : 'index.php';

$plugin = new Plugin(!empty($_GET['_p']) ? $_GET['_p'] : null);

define('Garradin\PLUGIN_ROOT', $plugin->path());
define('Garradin\PLUGIN_URL', WWW_URL . 'p/' . $plugin->id() . '/');
define('Garradin\PLUGIN_QSP', '?');

$plugin->call('public/' . $page);
