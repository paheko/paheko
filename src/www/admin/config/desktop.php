<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Users\Users;

require_once __DIR__ . '/_inc.php';

if (!DESKTOP_CONFIG_FILE) {
	throw new UserException('Cette page est désactivée');
}

function has_command(string $command) {
	if (PHP_OS_FAMILY === 'Windows') {
		$result = strtok(shell_exec('where ' . $command) ?? '', "\n");
	}
	else {
		$result = shell_exec('which ' . $command);
	}

	return trim((string)$result) !== '';
}

$csrf_key = 'desktop_config';
$config = Config::getInstance();
$win = PHP_OS_FAMILY === 'Windows';

$form->runIf('save', function() {

	$constants = [
		'LOCAL_LOGIN'      => intval($_POST['LOCAL_LOGIN'] ?? 0),
		'PDF_COMMAND'      => strval($_POST['PDF_COMMAND'] ?? '') ?: null,
		'CONVERSION_TOOLS' => array_keys((array)($_POST['CONVERSION_TOOLS'] ?? [])),
	];

	$email = $_POST['email'] ?? '';

	if ($email === 'php') {
		$constants['DISABLE_EMAIL'] = false;
		$constants['SMTP_HOST'] = null;
		$constants['SMTP_USER'] = null;
		$constants['SMTP_PASSWORD'] = null;
	}
	elseif ($email === 'smtp') {
		$constants['DISABLE_EMAIL'] = false;
		$constants['SMTP_HOST'] = strval($_POST['SMTP_HOST'] ?? '') ?: null;
		$constants['SMTP_PORT'] = intval($_POST['SMTP_PORT'] ?? '') ?: null;
		$constants['SMTP_USER'] = strval($_POST['SMTP_USER'] ?? '') ?: null;

		if (!empty($constants['SMTP_PASSWORD'])) {
			$constants['SMTP_PASSWORD'] = strval($_POST['SMTP_PASSWORD']);
		}
	}
	else {
		$constants['DISABLE_EMAIL'] = true;
	}

	Install::setConfig(DESKTOP_CONFIG_FILE, $constants);
}, $csrf_key, Utils::getSelfURI(['ok' => 1]));

$first_admin_user = Users::getFirstAdmin();

$pdf_commands = [
	'auto'        => 'Automatique',
	'prince'      => 'PrinceXML (recommandé)',
	'chromium'    => 'Chromium (recommandé)',
	'weasyprint'  => 'Weasyprint',
	'wkhtmltopdf' => 'wkhtmltopdf',
];

foreach (['prince', 'chromium', 'chrome', 'weasyprint', 'wkhtmltopdf'] as $cmd) {
	if (!has_command($cmd)) {
		unset($pdf_commands[$cmd]);
	}
}

$conversion_commands = ['mupdf', 'ssconvert', 'ffmpeg'];
$available_conversion_commands = [];

foreach ($conversion_commands as $cmd) {
	if (has_command($cmd)) {
		$available_conversion_commands[] = $cmd;
	}
}

if (count($pdf_commands) === 1) {
	unset($pdf_commands['auto']);
}

$tpl->assign([
	'pdf_commands'              => $pdf_commands,
	'can_configure_local_login' => Users::canConfigureDesktopLogin(),
	'first_admin_user_name'     => $first_admin_user ? $first_admin_user->name() : null,
]);

$constants = Install::getConstants();
$email_options = [
	'php' => 'Fonction mail() de PHP',
	'smtp' => 'Serveur SMTP',
];

if ($win) {
	unset($email_options['php']);
}

if (DISABLE_EMAIL) {
	$current_email_option = '';
}
elseif (SMTP_HOST) {
	$current_email_option = 'smtp';
}
else {
	$current_email_option = 'php';
}

$tpl->assign(compact('csrf_key',
	'win',
	'constants',
	'conversion_commands',
	'available_conversion_commands',
	'email_options',
	'current_email_option'
));

$tpl->display('config/desktop.tpl');
