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

// Prune old accounts
demo_prune_old();

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
		<p><button type="submit">Créer mon bac à sable</button></p>
		<p style="margin-top: 2em"><img alt="" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAALQAAAC4BAMAAAC4Idt6AAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAAlwSFlzAAAOsgAADrIBMTU+twAAABhQTFRFR3BMoIksoIksoIksoIksoIksoIksoIksDcngAAAAAAh0Uk5TAP4fRZds4b+c1PO7AAAM20lEQVR42rRbS2OisBaOAXTbVIVttNZusdParXR8bKW+tmKtbqXPv39PThIIyMzc2/SymBasH+E8vnPOF4aQ/+V4aJH/09FjjIufG/7TyDeMsRH8bLDmDyO7gMza8Es6bv64NZg/YHCLttd2juHPIgfJCowdjTx2iC5/FJm1om2XeAEhcbPRdE4/AkyXiOwPAG6whQvcDaKfCER6lwg7r9tsyQn1MfJo2krtze2lYsls7l4mEHp9ZWRnHl/ZYjsJIj8SevLgNMnwYubP7aA7/jhg7FWdNdr503xEF3bQySj+zTKXReZCG3bQrk/8XjDSKy2ERX1rBT3YOe3OMTsrBHNsZWuahN423emzoPBhasWAbovUV0xHRX9XuK1d0nR2ZN1vVy/TtSNAiIiX6FSN1bHz4oF4bz6vdpudF0mLuP3mH2z7ZcdMYOZIB3XjdPaZDTVdkl4WcFNe/swq9i5IogJuNi5B1UdW0I2tF8i1UsZKHDqw49TOqaGc6LQ+Sp992tFePKptdVqWaqFlLpI0HIx0F8LpVde0lZ0XqZ/lhcv2UG5auX0Hdl4EctK04WEZY/7TD5m6c0k0VTuI/CWKpDxv23oxSzlctd9qpmyuwpJYklO2uI6AhvqbMB/tPbDjJsD1NDel7B6wD3gDbl1hSP2CuCrEKAtIn6kDnsQJLL04yqDXol+QbRSLoIF3LaFjrlsNcGJLNe/gTPi9w+xs/Zm1GoMAh4xYm2QUMauMESxRO8mWgTGxfHocSOhADUwWdYDU5OIiP5bs5NFfdwj+akfWgFab6xFpPnvXBALgtgECUB2Ve7+ja5Y3ldeRJfRawYu2iQxJ9/4d82gFVq77dtBfeZXqPM0QFoyNmV5jVtWLNmVoo0GkMWKA3o9h7ugwK3byxLcj3UgKaE9ylRuSAXuyqgMCOtWJ2ZKJn80xVlNjY5QTnIPRNs27zA+rooshHcuF1pkIib3RtE+tRg20SlsZgGXTqKSAjQ30RrUL4t9hJJgu2mXT0rRpD11/lo8g/OZpN4J9uG0yCtI7SehL4tyvFotQFkq7eq6yJQ6ksdsECRWrTsQsJyTJTMNUmGH5dADiWC0xnqFrteMQ2WnQoxrAotDzuYc2FoXMytayBWnsHFmrOieSvsjhFHqStV1z/SkXSwYoTsBk11CBkTI74pOUIeojTYIQu1ZyP9c9mqWIKDonNHiP+b+NMXGwZy+20hCMurIU9BIWLPoq4Bz/5oXbQsMc85W3wAlDH9JoR39AK0uXijXc12vRlb1xAj/DH0CGBlLBDIH37r5w6dAoLH4AuqHzWS7emYo+GBrhvbWtyUD1qV7WO3LlVGvsjlp1r1Tr75KWrcFpIpd71pX2LFlV+BER6HkvnVr2k7oQVMTx89qydyfkptph3olE1q6MdpXtzxweaGcJ7VUuriEeyNokcVUs3OED2eruThVn/MZPrE0yrOgc36SPbZmqXtFKq1KQWu791CpWvdf6i92yq+Sad52UdtJFlXQ6ywUCq6QJ/xB84rZ22laV8uGpn4kdAR7+kqr+wQr6L4NtpxnbCAyVKqQnAzKdN2wi26syp4v3cwNs16yki/OLWNlEfEQW9NeoHD6jtmgrISxrFuFXrYCLiUzAK9NYzR1l6CiYSi3RwtjV4np0lag+3sLYkZmMv7Kr5Ga/PpGCJvC9sUMHBnvT2S+qDJrZQiI33dQ46s4Gb4ip+H212THTrcN7qks9yJQRvx+/nTEmI08JucaZSRFLFEx4JvTYZUybOKGXilkvUJJU0vz+/kzNCAAHPPYFqQIzqoSuN2nKO9+FNtV1F4zjfM7JQ6DoED70ePxd6NgI6/oJfReSuNvKC1D0Xej07Alumhpa7iIn3+1YRWjR5cSsknF4EAaZPskN2e8O1WjU4YfMacVEtV8tuDxkbHqBraxFxjjPhIy5aCxlKsa9lhO4bJKg+hn/FyWM3o8nhQtdtaZ6KMMbpmmkkPc6OwYJtH3xVlz8G/NdLT+ZP/n1ubjd5IoB3cBge3z02DPqW47aKkD9bAALFoaiEPMDRSHaH4VMfj8uOOkuD0KAjfdqSPbSYHJ7tZzBZD4BlFC6M/UTse7ozUlbcnjKFg23PVMynMLJMnq5FQ+SyuYcd2KaxLkgg1ck1Q8wBAUUJ2mh6DBUi16zr88/qiRqgpNo7JHcZ79v7wh/dLFc3YDz9jftDQNL+Tj8IQ9c4V7Khu0rsifMpUe6WN3P4PxZI7M5FbN53Oa4uskGAuOGHVHs6gfOYvmVKGM8MPa+4N1CnNMdTvbX+nxlrF/rTC7zf3NCI/YleoQEJ2maoIrB3p+kMHAtv1Ihmz/oJvSWZK/35PskcO5PFlBumxCn4N2J0EuY/z75JVAbOCjQq9VqPPMrsO81/ovoNNTRVK4Y6Avvn2r/jiZv2UayZ+gvveSNy4KdX1wZskcGvSV9CCOPN1jp2A7PehAaqtD13+fnMdgtRAocJ1jxkGQbx/nhM6OY30iH6Fi4Xy5KH2UTEM2QMSeEjOOUoZWWc2uoMfSMBem4yBwQCC/XrBAghN6WgFsrfHr6mAkZJVS8Z33GifeKco3gBWeWYs7pAHE5mjEtrVnEBVrU4aanbnX6PaLm1gjxFHDpHMLuOCHZdmub4OKujI3M/HEkaN1UdDxA9LRdnGRQN5kxftWEI44LqtuFcohUNXtOqbIelkMDutciJvRIa3C4V1owtnRbt6B3lSprs6WCRiA4h3CDN+qoxxbpv1VKgnmMoeiIGo9RtjHmVONI/LmD33XmggZGRG6H1vTS8jxam1HtrYKQvhk4a2UTjiIPGieAz/UrKxuWxVJd5aIpmUU5NOT/TeWw4YXq73uctLjYBkWIqcEs0muh4q1L1WAXMil6rZbqCgpEI8TUbxpc7ma0x7uEhAXfqt0TT9wAo14Q8ihPdNNGzk6omaaafj3NdngQNvTmVwa1qB0VXz89FLw5vQU7F1IdW81Zd+P/Nqq88tgpi/6JovFVkvsAUkJ2aHKxuJlcqGGQLxzY2BR3xcYDZnpI6+d6uTZ1Nt04I1zsIC1nEUDPSeODm2YW8Rehqb0zLksN0oJYl8kqbjU8G5kaIzIvTeCvirJ3SFnOSb8A7IxC6d8gi+Ghnz3tu+uXiNU9FaGvk2dlyEz0hCeWAHyEZV1DD6BOZCKlexklH8Uo8fJHEvVhJrsv+I5pI/JqdCtr/SLR3VwY6FnPxQ/CBw5f5dDo7Qg62vvxp6z8SC3iG41S2YAgvpHh7Wc39uR9KPRuw0Oxb6Vo/PXcS6H0L/QX+qKrO5m2c+aGK4OUBdrID0yELb6tGB2rZEww7cJY40GwTCicaUYUz2Lo8S7fLxjCuh1UX673ReixDOXCzpa70+t7u9Cm0BYX8Tj0uSF7DtkiaqvRsqj1Is8X7kenoRQS+i/gXwp0ZlpdtELRlgx2hvQC9+HqhcqHQs6QfvF267bSKPCFyVq+z62gr4TcZYwxjkiBk9xIFHU0CxHooP1C7t9kp88VFU8WKpgXo3zKHodYi84U6eiYFLZZ8hs5o3NQV4XQqfxi1aliPnU+iy9GinehZaXzeLFTRnvK7BIqXVoYBEb/fuuR7quuPpGMu7meVl09CNypovgvPbtfsgI+Ei32XXMp4vQSJEk9yv9TwJAdpRvSRYhliVf0iVxJfM4M95SVbPIvRUf9GSUjnnXMrrhW5+Sx0MDIGWkqqsiT5D5C/2qSmTE8ZebnZ9nuEtdfdOUI40+4Fub+Jgbg6z28EPilARD6JSfU9X0fZkXjn2+BrsV9q/aJvOnZYHg3+0QCpMugrK79UX3jlRLtUfzfitK4T6dYi/qvmES3/0vk1bjSOwXj/6d5q+ltGIShKE72E9YrYk3PUdVq12qptGu0tff20O2+rP+/gQDBAfIFk+Zj0oIx9vOzIXCq7VnXgh49F0mDw8ehcPlGmG/yvR9p4darKyPrCbuv+LEVGzAHRxxwOn6pKwtxjNyWGm+XFmLFravBeFlfb70K1Wc8fpQM7fsX4qm+kA98KW6WK1so5gqFNLJJC2WDLhlvx+2vB1NhFCAgA05UaLTbgYb8ZNs7Ju8Oh+ZJv+ojBTgilyK6drK3vA8dqw/LoT8RGi+Wd5/FNrXr6YzGoUqsmf2GIXcEwivJxubbyX1I/c+9nWnNFJkqjetZbU6qGwAbe8kZJZHEul/xKlwZeUHOKzjivIsxVVhntp2OKEFmAG0UmW8janQ4+MNy9jgO90NQVRaL103HbF6Fb2EqyRwnk+BVFUJmyu11AO233gIF3Pc10giDYwVpQBT9Ds9zCVDy7CbsC1DPc5BUxB/TkzOS0E+/nOsXwPFEo2lt1CiMjDSZosnRqEAiC3NUIP9IArLHKAIehqAIQhCko4j68/Pd39noPPWHD00C7z+HTVB0AAAAAElFTkSuQmCC" /></p>
	</fieldset>
	</form>
</body>
</html>';

