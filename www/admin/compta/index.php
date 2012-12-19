<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$journal = new Compta_Journal;

$tpl->display('admin/compta/index.tpl');

?>