<?php

namespace Paheko;

use KD2\Security;

require_once __DIR__ . '/demo/src/include/lib/KD2/Security.php';
$error = false;

if (isset($_POST['c'], $_POST['h'])
	&& Security::checkCaptcha(SECRET_KEY, $_POST['h'], $_POST['c'])) {
	$mode = $_POST['mode'] ?? null;

	if ($mode === 'example' && file_exists(EXAMPLE_SOURCE_PATH)) {
		$source = EXAMPLE_SOURCE_PATH;
	}
	elseif ($mode === 'bike' && file_exists(BIKE_EXAMPLE_SOURCE_PATH)) {
		$source = BIKE_EXAMPLE_SOURCE_PATH;
	}
	else {
		$source = null;
	}

	create_demo($source);
}
elseif (isset($_POST['c'])) {
	$error = true;
}

$captcha = Security::createCaptcha(SECRET_KEY, 'fr_FR');

echo '<!DOCTYPE html><head><title>Tester Paheko</title><style type="text/css">
body { font-family: sans-serif; font-size: 16pt; text-align: center; }
form {
	margin: 1em auto;
	max-width: 800px;
}
legend {
	padding: .2em;
	font-size: 1.3em;
	font-weight: bold;
}
fieldset { border: 1px solid #ccc; }
input, tt { font-size: 1.1em; padding: .5em; }
tt { background: #ddd; }
dd { margin: 1em 0; }
.help { border: 2px solid darkred; background: lightyellow; padding: .5em; border-radius: .5em; }
.s label { background: #eee; border-radius: .5em; display: block; text-align: left; padding: .5em; cursor: pointer; }
.s { margin-bottom: 2em; }
.login-help { visibility: hidden; }
.login-help.visible { visibility: visible; }
</style></head><body>
<form method="post">
';

if ($error) {
	echo '<p style="color:red">Le code est erroné.</p>';
}

echo '
	<fieldset>
		<legend>Créer une instance de test</legend>
		<p class="help">Ce formulaire permet de créer une instance de test <strong>temporaire</strong>.<br />Elle sera supprimée automatiquement après quelques jours&nbsp;!</p>

		<dl class="s">
			<dd><label><input type="radio" name="source" value="" checked="checked" onclick="document.querySelector(\'.login-help\').classList.toggle(\'visible\', !this.checked);" /> Créer une instance vierge</input></label></dd>';

foreach (EXAMPLE_ORGANIZATIONS as $label => $path) {
	printf('<dd><label><input type="radio" name="source" value="%s" onclick="document.querySelector(\'.login-help\').classList.toggle(\'visible\', this.checked);" /> Créer une instance à partir de l\'exemple de <q>%1$s</q></input></label></dd>', htmlspecialchars($label));
}

echo '
			<dd class="login-help"><em>Identifiant : demo@'. DEMO_PARENT_DOMAIN . ' / Mot de passe : paheko</em></dd>
		</dl>
		<input type="hidden" name="h" value="'.$captcha['hash'].'" />
		<dl>
			<dd><tt>'.$captcha['spellout'].'</tt></dd>
			<dt><label for="f_c_answer">Merci de recopier ici en chiffres (par exemple <em>1234</em>)<br />le nombre affiché ci-dessus&nbsp;:</label></dt>
			<dd><input name="c" type="text" maxlength=4 required="required" /></dd>
		</dl>
		<p><input type="submit" value="Créer mon instance &rarr;" /></p>
	</fieldset>
	</form>
</body>
</html>';

