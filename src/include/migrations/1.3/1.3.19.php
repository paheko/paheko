<?php

namespace Paheko;
use Paheko\Accounting\Charts;

$db->beginSchemaUpdate();
// Make sure 120 and 129 do exist
Charts::updateInstalled('fr_pcc_2020');

// Make sure 690/692 have the correct type
Charts::updateInstalled('be_pcmn_2019');

// Make sure rules are applied correctly
Charts::resetRules(['FR', 'BE']);
$db->commitSchemaUpdate();
