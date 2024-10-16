<?php

fwrite(STDERR, "This command is deprecated, please use 'bin/paheko upgrade' instead\n"); //FIXME 1.4

$_SERVER['argv'] = ['paheko', 'upgrade'];
require __DIR__ . '/../bin/paheko';
