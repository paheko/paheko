<?php

fwrite(STDERR, "This command is deprecated, please use bin/paheko instead\n"); //FIXME 1.4

$_SERVER['argv'] = array_merge(['paheko', 'storage'], array_slice($_SERVER['argv'], 1));
require __DIR__ . '/../bin/paheko';
