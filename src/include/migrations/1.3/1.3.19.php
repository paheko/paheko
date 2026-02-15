<?php

namespace Paheko;
use Paheko\Accounting\Charts;

$db->beginSchemaUpdate();
$db->import(__DIR__ . '/1.3.19.sql');

// Make sure 120 and 129 do exist
Charts::updateInstalled('fr_pcc_2020');

// Make sure 690/692 have the correct type
Charts::updateInstalled('be_pcmn_2019');

// Make sure rules are applied correctly
Charts::resetRules(['FR', 'BE']);

$db->import(__DIR__ . '/1.3.19.sql');

$db->commitSchemaUpdate();
