<?php

namespace Garradin;

use Garradin\Membres\Session;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess(Session::SECTION_DOCUMENTS, $session::ACCESS_READ);
