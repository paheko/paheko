<?php

fwrite(STDERR, "This command is deprecated, please use bin/paheko instead\n"); //FIXME 1.4

$_SERVER['argv'] = ['paheko', 'queue', 'run', ($_SERVER['argv'][2] ?? null) === '-q' ? '-q' : ''];
require __DIR__ . '/../bin/paheko';
