# La gestion d'association libre et simple

<nav id="gnav">

* [Guides d'installation](/wiki/?name=Installation)
* [Documentation](/wiki/?name=Documentation)
* <a href="https://paheko.cloud/" target="_blank">Essayer gratuitement</a>
* [Entraide](/wiki/?name=Entraide)

<ul id="news">
	<li><a href="$ROOT/wiki/?name=Changelog">Nouveautés</a></li>
	<li><a href="$ROOT/uvlist">Anciennes versions</a></li>
</ul>

</nav>

<p id="give"><a href="https://kd2.org/soutien.html" target="_blank">Soutenir Paheko en effectuant un don :-)</a></p>

<h3><a href="https://paheko.cloud/garradin-devient-paheko" target="_blank">Garradin devient Paheko !</a></h3>

<p>Garradin.eu est devenu Paheko.cloud !</p>

<form method="GET" action="$ROOT/wiki" onsubmit="var t = this.querySelector('[type=radio]:checked'); this.querySelector('[name=s]').name=t.dataset.name; this.action=t.dataset.action; this.target=t.dataset.target;">
<fieldset class="searchForm searchFormWiki">
	<legend>Rechercher</legend>
	<input type="search" name="s" size="40" value="" />
	<label><input type="radio" name="t" value="" data-name="s" data-action="/paheko/wiki" data-target="" checked="checked" /> Chercher dans la documentation technique</label>
	<label><input type="radio" name="t" value="1" data-action="https://paheko.cloud/search" data-name="search" data-target="_blank" /> Chercher dans l'aide utilisateur</label>
	<input type="submit" value="Rechercher" />
</fieldset>
</form>

<script type="text/javascript">
document.head.innerHTML += `<style type="text/css">
#give {
	text-align: center;
	padding: 1em;
}

#give a {
	display: inline-block;
	padding: .5em;
	padding-left: 70px;
	border-radius: .5em;
	font-size: 1.5em;
	background: #ffc url("https://kd2.org/soutien/coins.png") no-repeat .5em .5em;
	border: 2px solid #990;
}

#gnav ul {
	display: flex;
	padding: 0;
	margin: 1em;
	margin-bottom: 1em;
	font-size: 1.2em;
	list-style: none;
	justify-content: center;
	align-items: center;
}

#gnav li {
	margin: 0;
	padding: 0;
	font-size: 1.2em;
	margin: .5em;
	text-align: center;
}

#gnav li a {
	height: 100%;
	padding: .5rem;
	background: #ddf;
	color: black;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: .5em;
	border: 2px solid #99f;
	text-decoration: none;
}

#gnav li.last {
	height: 100%;
	padding: .5rem;
	display: block;
}

#gnav li a:hover {
	text-decoration: underline;
	opacity: 0.7;
}

#news li {
	font-size: 1em;
}

#news li a {
	border-color: #060;
	background: #dfd;
}

#download {
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
}

#download div, #download div h3 a, #download div h4 a {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}

#download div h3 a, #download div h4 a {
	background: #eef;
	border: 2px solid #ccf;
	padding: 5px;
}

#download a:hover {
	background: #fee;
	border-color: #fcc;
}

#download img {
	height: 124px;
	box-shadow: none;
	padding: 5px;
}

#download p, #download h3, #download h4 {
	margin: 0;
	margin-bottom: 8px;
}

.searchForm {
	border: 1px solid #ccc;
	border-radius: 5px;
	padding: .5em;
	margin: 1em auto;
	max-width: 30em;
	text-align: center;
}

.searchForm input[type=search] {
	border-color: #333;
}
`;

function isNewerVersion (oldVer, newVer) {
	const oldParts = oldVer.split('.')
	const newParts = newVer.split('.')
	for (var i = 0; i < newParts.length; i++) {
		const a = ~~newParts[i] // parse int
		const b = ~~oldParts[i] // parse int
		if (a > b) return true
		if (a < b) return false
	}
	return false
}

fetch('/paheko/juvlist?'+(+(new Date))).then((r) => {
	r.json().then((list) => {
		let last = {};
		let selected;

		list.forEach((file) => {
			var v = file.name.match(/^paheko-(\d+\.\d+\.\d+)\.(deb|exe|tar\.gz)$/);

			if (!v || v[1].match(/-(alpha|rc|beta)/)) {
				return;
			}

			file.type = v[2];
			file.version = v[1];
			file.human_size = (Math.round((file.size / 1024 / 1024) * 10) / 10 + ' Mo').replace(/\./, ',');

			if (!last.hasOwnProperty(file.type) || isNewerVersion(last[file.type].version, file.version)) {
				last[file.type] = file;

				if (file.type == 'tar.gz') {
					selected = file;
				}
			}
		});

		let days = ((+new Date)/1000 - selected.mtime) / 3600 / 24;

		if (days < 31) {
			time = Math.ceil(days) + ' jours';
		}
		else if (days >= 31) {
			time = Math.round(days / 30.5) + ' mois';
		}

		document.querySelector('#news').innerHTML = `<li class="last"><strong>Dernière version : ${last['tar.gz'].version}</strong></li>
			<li class="last"><em>il y a ${time}</em></li>` + document.querySelector('#news').innerHTML;

		document.querySelector('#news').insertAdjacentHTML('afterend', `<div id="download">
			<div>

				<h3><a href="$ROOT/uv/${last['tar.gz'].name}"><img src="data:image/svg+xml;base64,PHN2ZwogIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICB3aWR0aD0iMjQiCiAgaGVpZ2h0PSIyNCIKICB2aWV3Qm94PSIwIDAgMjQgMjQiCiAgZmlsbD0ibm9uZSIKICBzdHJva2U9ImN1cnJlbnRDb2xvciIKICBzdHJva2Utd2lkdGg9IjIiCiAgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIgogIHN0cm9rZS1saW5lam9pbj0icm91bmQiCj4KICA8cmVjdCB4PSIyIiB5PSIyIiB3aWR0aD0iMjAiIGhlaWdodD0iOCIgcng9IjIiIHJ5PSIyIiAvPgogIDxyZWN0IHg9IjIiIHk9IjE0IiB3aWR0aD0iMjAiIGhlaWdodD0iOCIgcng9IjIiIHJ5PSIyIiAvPgogIDxsaW5lIHgxPSI2IiB5MT0iNiIgeDI9IjYuMDEiIHkyPSI2IiAvPgogIDxsaW5lIHgxPSI2IiB5MT0iMTgiIHgyPSI2LjAxIiB5Mj0iMTgiIC8+Cjwvc3ZnPgo=" alt="" /><span>Serveur</span></a></h3>
				<p>pour auto-hébergement</p>
				<p><small>(.tar.gz, ${last['tar.gz'].human_size})</small></p>

			</div>
			<div>

				<h4><a href="$ROOT/uv/${last['deb'].name}"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGUAAAB8AgMAAAANy5YPAAAADFBMVEX////IAjfrobXZTnPqtJDBAAAACXBIWXMAAAsSAAALEgHS3X78AAADUklEQVRIx51WPWsbQRBdrZDxSZZJo14JHAQcgxv1+gdx4dFdIZQrg0khUgnj4nCXIpDmejdpbBICwSGNQT8hzcVl1KRyEwJOEQjO7ezX7J1GhbfR6d7O7Js3H3tC0HVxLtYuubcSzLqCiy8yX4dEAHD5Y7bOXQm4Fk1oH8xacUYAs27OGAGUhyFUegimDXpuJb8oNKIQTAjShnARIrF5df9P/849NMAXx5W4e8jHayIJsR4+1/hlxHkWHDWjLlzUhfp36pPj6eO+mUtwX0VNjkrykNRSP3dquhUhJxIkbk39rnlNa+NFMaLpk46ipCE6N/jQosK4FC2N6zCvyGNc/a76dX/IQ73K4iB1NrOVijIfBQm3nFNdgWkdKuB4oQlmdWgA47k+s9EeMWTvkGnSaIAOnB7gDh/V3v3KpGzyFEM/JKR13iKY7AgxLDyLkc1GC5IKelS6hMgqAfLGmHeF+OS5R0rTXqaTIYV4opVEysjnK0LJGdrasAo039Wuz1AMpxwRMZHI01Kf0ibNVXRHtjTTGtRxuscZhVAnK8Zg2YBsxIWoOYxdxDcBNFdnWWgSpDlTkBFDhh0xJlDLQlvocEmgyAax6+MynCMbRNeTr0NaQ4SM8P1D2kYzzkqW08CK1nCZDgkUWfJDdVZ6vgaSQ3XWAjW0RWPypqZyG14GkNFwZ6mqYkyhloH2czW/cwq1TVf0UF0pCCRJL5WTABLgC6A82qYhy7LKnrxFHF5tY5dZqFBFcpVr3wEkfqZ+WKIkLpXDDxPfZ6ZtTdk8e+zsyxfG2NTh1pkrBXhtIGMVHRT6MPne7ImsVftjrA/rlYkIIfH8jb4n3bRouXb4/BvfVTMrs5AL589Imd256dP2UPwW4PbKDfJKFFfrrZPwzpOuYatQ78L7nMzQ/fk1wEmtN42H76J7HrSS99A/ycNhmtD7fhZeBWRrt3YVjJnvls6aie1viW8MVI025hNJTUzuLBHehrUPlJSDBkTgxgUyFWxgrFW05r7xN0nOB8ZBevitXyM+5piPOWrevp4iy75SccWr+DAerMByA4+Cl2pDyjbkRQIZa43IFhzU3yTj0Qb6S95jyhdI+PkcVsH0Ifkc8UTafNKqwcCWgbiGvyx2cUnt/gORWTpBwiiSSwAAAABJRU5ErkJggg==" alt="" /><span>Linux</span></a></h4>
				<p>hors-ligne, pour ordinateur</p>
				<p><small>(.deb, ${last['deb'].human_size})</small></p>

			</div>
			<div>

				<h4><a href="$ROOT/uv/${last['exe'].name}"><img src="data:image/svg+xml;base64,PHN2ZyBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAyNCAyNCIgdmlld0JveD0iMCAwIDI0IDI0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Im0yMiAyLTEwLjggMS42djhsMTAuOC0uMXptLTExLjggMTAuNS04LjItLjF2Ni44bDguMSAxLjF6bS04LjItNy43djYuOGg4LjF2LTcuOXptOS4xIDcuN3Y3LjlsMTAuOSAxLjZ2LTkuNHoiLz48L3N2Zz4=" alt="" /><span>Windows</span></a></h4>
				<p>hors-ligne, pour ordinateur</p>
				<p><small>(.exe, ${last['exe'].human_size})</small></p>

			</div>

		</div>`);
	});
});
</script>

## C'est quoi ?

<a href="$ROOT/raw/7bb068963b9f6301b27b81fe925caae9e86a229b?m=image/png" target="_blank" style="float: right; margin: 1em;"><img src="/paheko/raw/7bb068963b9f6301b27b81fe925caae9e86a229b?m=image/png" alt="Liste des membres" width="400" /></a>

Paheko (anciennement appelé <em>Garradin</em>) est un logiciel de gestion d'association (loi 1901 / ASBL / etc.). Son but est de permettre :

*  la gestion des __adhérent⋅e⋅s__ : ajout, modification, suppression, possibilité de choisir les informations présentes sur les fiches adhérent, envoi de mails collectifs aux adhérent⋅e⋅s
*  la tenue de la __comptabilité__ : avoir une gestion comptable complète à même de satisfaire un expert-comptable tout en restant à la portée de celles et ceux qui ne savent pas ce qu'est la comptabilité à double entrée, permettre la production des rapports et bilans annuels et de suivre au jour le jour le budget de l'association
*  la gestion des __cotisations__ et __activités__ : suivi des cotisations à jour, inscriptions et paiement des activités, rappels automatiques par e-mail, etc.
*  le travail __collaboratif__ et __collectif__ : gestion fine des droits d'accès aux fonctions, échange de mails entre membres…
*  la __simplification administrative__ : prise de notes en réunion, archivage et partage de fichiers (afin d'éliminer le besoin d'archiver les documents papier), etc.
*  la publication d'un __site web__ pour l'association, simple mais suffisamment flexible pour pouvoir adapter le fonctionnement à la plupart des besoins
*  l'__autonomisation des adhérents__ : possibilité de mettre à jour leurs informations par eux-même, ou de s'inscrire seul depuis un ordinateur ou un smartphone
*  la possibilité d'adapter aux besoins spécifiques de chaque association via des [__extensions__](/wiki/?name=Extensions).

Tous ces objectifs ne sont pas encore réalisés, voir :

* [la liste des fonctionnalités disponibles](/wiki/?name=Fonctionnalités) pour ce qui est actuellement disponible ;
* [la feuille de route](/wiki/?name=Roadmap) pour la liste des fonctionnalités qu'il reste à implémenter.

Paheko est un logiciel libre disponible sous licence [AGPL v3](https://www.gnu.org/licenses/why-affero-gpl.fr.html).

Paheko signifie *coopérer* en *Māori*, langue indigène de la Nouvelle-Zélande.

## Documentation et entraide

* D'abord lire la [documentation](/wiki/?name=Documentation) et notamment la [foire aux questions](/wiki/?name=FAQ)
* Voir la page [Entraide](/wiki/?name=Entraide) pour accéder aux listes de discussion et au salon de discussion IRC

## Participer

Tout coup de main est le bienvenu, pas besoin d'avoir des connaissances techniques ! Nous avons un [guide de contribution](/wiki/?name=Contribuer) pour vous aider à voir comment vous pouvez participer à Paheko :)

### Développement

Paheko est un logiciel libre, développé en PHP, utilisant la base de données SQLite, et avec une interface utilisant HTML, CSS et un peu de Javascript.

Nous acceptons les contributions (plugins, patch, code, tickets, etc.) avec plaisir, consultez la [documentation développeur⋅euse](/wiki/?name=Documentation développeur) pour découvrir comment vous pouvez contribuer.
