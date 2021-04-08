<?php

namespace Garradin;

const UPGRADE_PROCESS = true;

require_once __DIR__ . '/../../include/test_required.php';
require_once __DIR__ . '/../../include/init.php';

if (!Upgrade::preCheck()) {
	throw new UserException('Aucune mise à jour à effectuer, tout est à jour :-)');
}

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, target-densitydpi=device-dpi" />
    <link rel="stylesheet" type="text/css" href="static/admin.css" media="all" />
    <script type="text/javascript" src="static/scripts/loader.js"></script>
    <title>Mise à jour</title>
</head>
<body>
<header class="header">
    <nav class="menu"></nav>
    <h1>Mise à jour de Garradin vers la version '.garradin_version().'...</h1>
</header>
<main>
<div id="loader" class="loader" style="margin: 2em 0; height: 50px;"></div>
<script>
animatedLoader(document.getElementById("loader"), 5);
</script>';

flush();

Upgrade::upgrade();

echo '<h2>Mise à jour terminée.</h2>
<p><a href="'.ADMIN_URL.'">Retour</a></p>

<script type="text/javascript">
window.setTimeout(function () { 
    window.location.href = "'.ADMIN_URL.'"; 
    stopAnimatedLoader();
}, 1000);
</script>
</main>
</body>
</html>';
