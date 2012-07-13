<?php

require_once __DIR__ . '/_inc.php';

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

$tpl->display('admin/compta/index.tpl');

?>