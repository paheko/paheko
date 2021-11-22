<?php

$files = [
	__DIR__ . '/../src/include/lib/KD2/Security.php',
	__DIR__ . '/../src/include/lib/KD2/HTTP.php',
	__DIR__ . '/../src/include/lib/KD2/FossilInstaller.php',
];

$template = <<<'EOF'
<?php

// Copier ce fichier dans un nouveau répertoire vide
// Et s'y rendre avec un navigateur web :-)

namespace KD2 {
	##KD2
}

namespace {
	const WEBSITE = 'https://fossil.kd2.org/garradin/';
	const INSTALL_DIR = __DIR__ . '/.install';

	mkdir(INSTALL_DIR);

	$i = new KD2\FossilInstaller(WEBSITE, __DIR__, INSTALL_DIR, '!^garradin-(.*)\.tar\.gz$!');
	$i->autoinstall();

	echo '
	<!DOCTYPE html>
	<html>
	<head>
	<meta charset="utf-8" />
	</head>

	<body>
	<h2>Installation réussie</h2>
	<p>Configurez désormais votre sous-domaine pour pointer sur le sous-répertoire <strong>www</strong> de cette installation.</p>
	<p><a href="' . WEBSITE . '">Consultez la documentation pour plus d\'infos</a></p>
	</body>
	</html>
	';

	$i->prune(0);
	@rmdir(INSTALL_DIR);
	@unlink(__FILE__);
}
?>
EOF;

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

$template = str_replace('##KD2', $source, $template);

echo $template;
