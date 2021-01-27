<?php

namespace Garradin;

use Garradin\Web\Skeleton;

require_once __DIR__ . '/_inc.php';

$s = new Skeleton('content.css');
$s->serve();
