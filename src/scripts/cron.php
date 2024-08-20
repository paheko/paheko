<?php

fwrite(STDERR, "This command is deprecated, please use bin/paheko instead\n"); //FIXME 1.4

$_SERVER['argv'] = ['paheko', 'cron'];
require __DIR__ . '/../bin/paheko';
