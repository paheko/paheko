<?php

namespace Paheko;

use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess(Session::SECTION_DOCUMENTS, Session::ACCESS_READ);
