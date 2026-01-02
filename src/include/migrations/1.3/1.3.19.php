<?php

namespace Paheko;
use Paheko\Accounting\Charts;

$db->beginSchemaUpdate();
// Make sure 120 and 129 do exist
Charts::updateInstalled('fr_pcc_2020');

// Make sure 690/692 have the correct type
Charts::updateInstalled('be_pcmn_2019');

$db->import(__DIR__ . '/1.3.19.sql');

$db->commitSchemaUpdate();

