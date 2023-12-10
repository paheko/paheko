<?php

$includes = [
	'##KD2' => [
		__DIR__ . '/../src/include/lib/KD2/Security.php',
		__DIR__ . '/../src/include/lib/KD2/HTTP.php',
		__DIR__ . '/../src/include/lib/KD2/FossilInstaller.php',
	],
];

$template = <<<'EOF'
<?php

// Copier ce fichier dans un nouveau répertoire vide
// Et s'y rendre avec un navigateur web :-)


namespace KD2 {
	##KD2
}

namespace {
	const WEBSITE = 'https://fossil.kd2.org/paheko/';
	const INSTALL_DIR = __DIR__ . '/.install';

	echo '
	<!DOCTYPE html>
	<html>
	<head>
	<meta charset="utf-8" />
	<style type="text/css">
	body {
		font-family: sans-serif;
	}
	h2, p {
		margin: 0;
		margin-bottom: 1rem;
	}
	div {
		position: relative;
		border: 1px solid #999;
		max-width: 500px;
		padding: 1em;
		border-radius: .5em;
	}
	.spinner h2::after {
		display: block;
		content: " ";
		margin: 1rem auto;
		width: 50px;
		height: 50px;
		border: 5px solid #000;
		border-radius: 50%;
		border-top-color: #999;
		animation: spin 1s ease-in-out infinite;
	}

	@keyframes spin { to { transform: rotate(360deg); } }
	</style>';

	function exception_error_handler($severity, $message, $file, $line) {
		if (!(error_reporting() & $severity)) {
			return;
		}
		throw new ErrorException($message, 0, $severity, $file, $line);
	}

	function mini_exception_handler($e) {
		printf('
		<div style="padding: 1rem;
			background: #fee;
			border: 2px solid darkred;"><h2>%s</h2>
			<h3>in %s:%d</h3>
			<pre>%s</pre>
		</div>',
		$e->getMessage(), $e->getFile(), $e->getLine(), (string) $e);
	}

	set_error_handler("exception_error_handler");

	set_exception_handler('mini_exception_handler');

	if (!version_compare(phpversion(), '7.4', '>=')) {
		throw new \Exception('PHP 7.4 ou supérieur requis. PHP version ' . phpversion() . ' installée.');
	}

	if (!class_exists('SQLite3')) {
		throw new \Exception('Le module de base de données SQLite3 n\'est pas disponible.');
	}

	$v = \SQLite3::version();

	if (!version_compare($v['versionString'], '3.16', '>=')) {
		throw new \Exception('SQLite3 version 3.16 ou supérieur requise. Version installée : ' . $v['versionString']);
	}

	$step = $_GET['step'] ?? null;
	$error = null;

	@mkdir(INSTALL_DIR);
	$i = new KD2\FossilInstaller(WEBSITE, __DIR__, INSTALL_DIR, '!^paheko-(.*)\.tar\.gz$!');

	if ($step == 'download') {
		$latest = $i->latest();

		if (!$latest) {
			die('</head><h1>Aucune version à télécharger n\'a été trouvée.</h1>');
		}

		$i->download($latest);
		$next = 'install';
	}
	elseif ($step == 'install') {
		$latest = $i->latest();

		if (!$latest) {
			die('</head><h1>Aucune version à télécharger n\'a été trouvée.</h1>');
		}

		$i->install($latest);
		$i->clean($latest);

		if (class_exists('\OCP\AppFramework\Controller')) {
			$next = 'nc' . time();
		}
		else {
			$next = null;
		}
	}
	else {
		$next = 'download';
	}

	echo $next ? '<meta http-equiv="refresh" content="0;url=?step='.$next.'" />' : '';

	echo '
	</head>';

	if ($step == 'download') {
		echo '
		<div class="spinner">
			<h2>Décompression en cours…</h2>
		</div>';
	}
	elseif ($step == 'install') {
		echo '<div>
			<h2>Installation réussie</h2>
			<p>Configurez désormais votre sous-domaine pour pointer sur le sous-répertoire <strong>www</strong> de cette installation.</p>
			<p><a href="' . WEBSITE . '">Consultez la documentation pour plus d\'infos</a></p>
		</div>';
	}
	else {
		echo '
		<div class="spinner">
			<h2>Téléchargement en cours…</h2>
		</div>';
	}

	echo '
	</body>
	</html>
	';

	if ($step == 'install') {
		$i->prune(0);
		@rmdir(INSTALL_DIR);
		@unlink(__FILE__);
	}
}

?>
EOF;

foreach ($includes as $tag => $files) {
	$source = [];

	foreach ($files as $file) {
		$content = file_get_contents($file);
		$content = preg_replace('!^(?:<\?php|namespace |use ).*$!m', '', $content);
		$content = preg_replace("!^!m", "\t$0", $content);
		$content = preg_replace("!^\t$!m", '', $content);
		$content = trim($content);
		$source[] = $content;
	}

	$source = implode("\n\n", $source);

	$template = str_replace($tag, $source, $template);
}

echo $template;
