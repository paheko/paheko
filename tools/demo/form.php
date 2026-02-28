<?php

namespace Paheko;

use KD2\Security;

$error = false;

if (isset($_POST['c'], $_POST['h'])
	&& Security::checkCaptcha(SECRET_KEY, $_POST['h'], $_POST['c'])) {
	demo_create($_POST['source'] ?? null);
	exit;
}
elseif (isset($_POST['c'])) {
	$error = true;
}

$captcha = Security::createCaptcha(SECRET_KEY, 'fr_FR');

echo '<!DOCTYPE html><head><title>Bac à sable Paheko</title><style type="text/css">
body {
	font-family: sans-serif;
	font-size: 16pt;
	text-align: center;
	background: Cornsilk;
}
form {
	margin: 1em auto;
	max-width: 800px;
	padding: 1em;
	background: #fff;
	border-radius: 1em;
}
legend {
	padding: .2em;
	font-size: 1.3em;
	font-weight: bold;
}
fieldset { border: 3px solid Wheat; border-radius: 1em; }
input, tt, button, .s label { font: inherit; font-size: 1.1em; padding: .5em; border: 2px solid #ccc; border-radius: .5em; }
tt { font-family: monospace; }
input:focus, button:focus { outline: none; box-shadow: 0px 0px 10px darkorange; }
tt { background: #ddd; }
dd { margin: 1em 0; }
.help { box-shadow: 0px 0px 10px darkred; background: lightyellow; padding: .5em; border-radius: .5em; }
.s label { display: block; text-align: left; cursor: pointer; }
.s { margin-bottom: 2em; }
.login-help { visibility: hidden; }
.login-help.visible { visibility: visible; }
button {
	border: none;
	background: #eee;
	box-shadow: 0px 0px 10px #666;
	font-size: 1.2em;
	cursor: pointer;
}
button svg {
	vertical-align: middle;
	margin-left: .5em;
}
</style></head><body>
<form method="post">
';

if ($error) {
	echo '<p style="color:red">Le code est erroné.</p>';
}

echo '
	<fieldset>
		<legend>Créer un bac à sable</legend>
		<p class="help">Ce formulaire permet de créer une instance de test <strong>temporaire</strong>.<br />Elle sera supprimée automatiquement après ' . DEMO_DELETE_DAYS . ' jours&nbsp;!</p>

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
		<p><button type="submit">Créer mon bac à sable 
<svg version="1.1" width="32" height="32" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
	 viewBox="0 0 512 512" xml:space="preserve">
<path style="fill:#EF8770;" d="M316.351,228.298v41.962c0,8.987-7.353,16.34-16.34,16.34h-275.5c-8.987,0-16.34-7.353-16.34-16.34
	v-41.962h32.681H283.67L316.351,228.298L316.351,228.298z"/>
<path style="fill:#CDE2A3;" d="M316.351,162.26v66.037H283.67V162.26c0-66.941-54.468-121.409-121.409-121.409
	S40.851,95.319,40.851,162.26v66.037H8.17V162.26C8.17,77.159,77.159,8.17,162.26,8.17S316.351,77.159,316.351,162.26z"/>
<polygon style="fill:#ED6951;" points="300.01,286.6 289.541,503.83 225.247,503.83 225.247,465.702 192.566,465.702 
	192.566,503.83 131.954,503.83 131.954,465.702 99.274,465.702 99.274,503.83 34.979,503.83 24.511,286.6 "/>
<path style="fill:#AECE71;" d="M474.014,62.791c16.395,0,29.816,13.41,29.816,29.816v100.287c0,16.395-11.82,34.282-26.254,39.751
	c-14.445,5.469-26.265,9.935-26.265,9.935v151.356c20.534,9.565,34.772,30.371,34.772,54.512v39.043
	c0,8.987-7.353,16.34-16.34,16.34h-87.541c-8.987,0-16.34-7.353-16.34-16.34v-39.043c0-24.14,14.238-44.947,34.772-54.512V242.579
	c0,0-11.82-4.466-26.254-9.935c-14.445-5.469-26.254-23.356-26.254-39.751V92.607c0-16.406,13.41-29.816,29.805-29.816h2.865
	v130.102h90.352V62.791H474.014z M453.403,471.149v-22.702c0-15.12-12.299-27.43-27.43-27.43c-15.12,0-27.43,12.31-27.43,27.43
	v22.702H453.403z"/>
<rect x="380.797" y="62.791" style="fill:#CDE2A3;" width="90.352" height="130.102"/>
<g>
	<path style="fill:#700019;" d="M390.375,448.451v22.698c0,4.512,3.657,8.17,8.17,8.17h54.864c4.513,0,8.17-3.658,8.17-8.17v-22.698
		c0-19.631-15.971-35.603-35.603-35.603S390.375,428.821,390.375,448.451z M445.238,448.451v14.528h-38.523v-14.528
		c0-10.621,8.641-19.262,19.262-19.262S445.238,437.831,445.238,448.451z"/>
	<path style="fill:#700019;" d="M474.017,54.62h-96.082c-20.944,0-37.983,17.04-37.983,37.983v100.292
		c0,19.881,13.851,40.697,31.535,47.392l20.978,7.939v140.736c-21.285,12.021-34.772,34.825-34.772,59.489v39.039
		c0,13.516,10.996,24.511,24.511,24.511h87.545c13.515,0,24.511-10.995,24.511-24.511v-39.038c0-24.664-13.487-47.468-34.772-59.489
		V248.226l20.979-7.94c17.683-6.692,31.534-27.509,31.534-47.39V92.602C512,71.659,494.96,54.62,474.017,54.62z M495.66,192.895
		c0,12.875-9.803,27.879-20.978,32.109l-26.258,9.938c-3.177,1.203-5.278,4.244-5.278,7.641v151.358
		c0,3.177,1.841,6.066,4.721,7.407c18.256,8.501,30.051,26.991,30.051,47.104v39.037c0,4.505-3.665,8.17-8.17,8.17h-87.545
		c-4.506,0-8.17-3.666-8.17-8.17v-39.038c0-20.114,11.796-38.604,30.051-47.104c2.88-1.341,4.721-4.23,4.721-7.407V242.582
		c0-3.397-2.101-6.439-5.278-7.641l-26.257-9.937c-11.176-4.23-20.979-19.235-20.979-32.109V92.602
		c0-11.934,9.708-21.642,21.642-21.642h96.082c11.934,0,21.642,9.709,21.642,21.642v100.293H495.66z"/>
	<path style="fill:#700019;" d="M471.149,87.3c-4.513,0-8.17,3.658-8.17,8.17v89.254h-29.662c-4.513,0-8.17,3.658-8.17,8.17
		c0,4.512,3.657,8.17,8.17,8.17h37.832c4.513,0,8.17-3.658,8.17-8.17V95.471C479.319,90.958,475.662,87.3,471.149,87.3z"/>
	<path style="fill:#700019;" d="M400.636,184.724h-11.663V95.471c0-4.512-3.657-8.17-8.17-8.17c-4.513,0-8.17,3.658-8.17,8.17
		v97.424c0,4.512,3.657,8.17,8.17,8.17h19.833c4.513,0,8.17-3.658,8.17-8.17C408.806,188.382,405.148,184.724,400.636,184.724z"/>
	<path style="fill:#700019;" d="M162.26,0C72.79,0,0,72.79,0,162.26v108.004c0,10.771,6.986,19.934,16.662,23.214l10.156,210.745
		c0.209,4.355,3.801,7.777,8.16,7.777h64.298c4.512,0,8.17-3.658,8.17-8.17v-29.957h16.34v29.957c0,4.512,3.658,8.17,8.17,8.17
		h60.604c4.512,0,8.17-3.658,8.17-8.17v-29.957h16.34v29.957c0,4.512,3.658,8.17,8.17,8.17h64.298c4.36,0,7.951-3.423,8.16-7.777
		l10.156-210.745c9.676-3.28,16.662-12.444,16.662-23.214V162.26C324.521,72.79,251.732,0,162.26,0z M300.01,278.434H85.847
		c-4.512,0-8.17,3.658-8.17,8.17c0,4.512,3.658,8.17,8.17,8.17h205.59l-9.68,200.885h-48.343v-29.957c0-4.512-3.658-8.17-8.17-8.17
		h-32.681c-4.512,0-8.17,3.658-8.17,8.17v29.957h-44.264v-29.957c0-4.512-3.658-8.17-8.17-8.17H99.278
		c-4.512,0-8.17,3.658-8.17,8.17v29.957H42.765l-9.681-200.885h20.082c4.512,0,8.17-3.658,8.17-8.17c0-4.512-3.658-8.17-8.17-8.17
		H24.511c-4.505,0-8.17-3.666-8.17-8.17v-33.796h291.84v33.795C308.18,274.769,304.516,278.434,300.01,278.434z M308.18,220.127
		H16.34V162.26c0-80.46,65.46-145.92,145.92-145.92S308.18,81.8,308.18,162.26V220.127z"/>
	<path style="fill:#700019;" d="M162.26,32.681c-71.45,0-129.58,58.129-129.58,129.58v33.356c0,4.512,3.658,8.17,8.17,8.17
		s8.17-3.658,8.17-8.17V162.26c0-62.44,50.799-113.239,113.239-113.239S275.5,99.82,275.5,162.26v33.356
		c0,4.512,3.657,8.17,8.17,8.17c4.513,0,8.17-3.658,8.17-8.17V162.26C291.84,90.81,233.712,32.681,162.26,32.681z"/>
</g>
</svg></button></p>
	</fieldset>
	</form>
</body>
</html>';

