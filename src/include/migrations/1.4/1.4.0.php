<?php

namespace Paheko;
use Paheko\Accounting\Charts;
use Paheko\Entities\Files\File;
use Paheko\Files\Files;

$db::toggleAuthorizer($db, false);
$db->beginSchemaUpdate();
$db->import(__DIR__ . '/1.4.0.sql');

// Make sure 74* are correct
Charts::updateInstalled('fr_pca_2025');

// Import rules from acc_tools plugin
$config = $db->firstColumn('SELECT config FROM plugins WHERE name = \'acc_tools\';');
$config = json_decode($config ?? 'null');

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
		]);
	}

	unset($config->rules);
	$db->update('plugins', ['config' => json_encode($config)], 'name = \'acc_tools\'');
}

$db->commitSchemaUpdate();

$old_path = File::CONTEXT_CONFIG . '/admin_homepage.skriv';
$file = Files::get($old_path);

if ($file && ($content = $file->fetch())) {
	$new_path = Config::FILES['admin_homepage'];
	$content = Utils::skrivToMarkdown($content);
	Files::createFromString($new_path, $content);
}

if ($file) {
	$file->delete();
}