<?php

fwrite(STDERR, "This command is deprecated, please use 'bin/paheko queue bounce' instead\n"); //FIXME 1.4

$_SERVER['argv'] = ['paheko', 'queue', 'bounce'];
require __DIR__ . '/../bin/paheko';
