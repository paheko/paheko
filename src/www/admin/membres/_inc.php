<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ACCES);

$membres = new Membres;
