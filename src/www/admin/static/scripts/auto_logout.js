(function () {
	var last_activity = +new Date;
	var t = null;
	var dialog = null;
	var logout_url = g.admin_url + 'logout.php';
	var tpl = `<form method="get" action="${logout_url}" target="_parent">
		<fieldset>
		<legend>Inactivité</legend>
		<h3 class="warning">Votre session est inactive. Voulez-vous rester connecté ?</h3>
		<p class="alert block" id="logout_timer">Vous serez déconnecté dans <span>60</span> secondes…</p>
		<p class="actions"><button type="submit" data-icon="⤝">Me déconnecter</button></p>
		<p class="submit"><button id="stay_logged_in" data-icon="⇥" type="button">Rester connecté</button></p>
		</fieldset>
		</form>`;

	function autoLogout() {
		window.clearTimeout(t);

		var session_activity = parseInt(sessionStorage.getItem('last_activity') || 0, 10);

		// Just in case activity happened in another tab and not this one
		if (session_activity > last_activity) {
			last_activity = session_activity;
			var expiry = last_activity + g.auto_logout*60*1000;

			if (expiry > Date.now()) {
				t = window.setTimeout(autoLogout, g.auto_logout*60*1000);
				return;
			}
		}

		dialog = g.openDialog(tpl, {close: false});
		var timer = document.querySelector('#logout_timer span');
		var title = document.title;
		var i = window.setInterval(() => {
			var c = parseInt(timer.innerText, 10);
			timer.innerText = c - 1;
			document.title = (c % 2 == 0) ? '⚠ Déconnexion' : title;
		}, 1000);
		t = window.setTimeout(() => window.location.href = logout_url, 60*1000);

		document.getElementById('stay_logged_in').onclick = () => {
			window.clearInterval(i);
			g.closeDialog();
			document.title = title;
			dialog = null;
			registerActivity();
		};
	}

	function registerActivity() {
		if (dialog) {
			return;
		}

		last_activity = +new Date;
		sessionStorage.setItem('last_activity', last_activity);

		if (t) {
			window.clearTimeout(t);
		}

		t = window.setTimeout(autoLogout, g.auto_logout*60*1000);
	}

	window.addEventListener('mousemove', registerActivity);
	window.addEventListener('scroll', registerActivity);
	window.addEventListener('keydown', registerActivity);

	registerActivity();
})();