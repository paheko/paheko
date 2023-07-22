<?php
namespace Paheko;

use Paheko\Email\Mailings;

require_once __DIR__ . '/_inc.php';

Mailings::anonymize();

$list = Mailings::getList();
$list->loadFromQueryString();


$tpl->assign(compact('list'));

$tpl->display('users/mailing/index.tpl');
