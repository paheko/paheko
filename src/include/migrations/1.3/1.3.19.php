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

/*
// Import rules from acc_tools plugin
$config = $db->firstColumn('SELECT config FROM plugins WHERE name = \'acc_tools\';');
$config = json_decode($config);

if (isset($config->rules)) {
	foreach ($config->rules as $rule) {
		$account = $rule->only_if === 'negative' ? $rule->credit : $rule->debit;

		$db->insert('acc_import_rules', [
			'regexp'         => 1,
			'match_label'    => $rule->match,
			'min_amount'     => $rule->only_if === 'positive' ? 0 : null,
			'max_amount'     => $rule->only_if === 'negative' ? 0 : null,
			'match_account'  => substr($account, 0, 3) === '512' ? $account : null,
			'target_account' => $rule->only_if === 'negative' ? $rule->debit : $rule->credit,
			'new_label'      => $rule->new_label ?? null,
		];
	}

	unset($config->rules);
	$db->update('plugins', ['config' => json_encode($config)], 'name = \'acc_tools\'');
}
 */

$db->commitSchemaUpdate();
