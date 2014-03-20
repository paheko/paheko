<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$page = utils::get('page') ?: 'index.php';

$plugin = new Plugin(utils::get('id'));

$tpl->assign('plugin', $plugin->getInfos());

$plugin->call($page);

?>