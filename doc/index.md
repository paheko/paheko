# La gestion d'association libre et simple

<div id="prez">
	<figure>
		<img src="./selfhost2.png" alt="Illustration d'une personne aidant une autre à installer Paheko sur un ordinateur" />
	</figure>

### Paheko — la gestion d'association simple</h3>

**Paheko** <small>(anciennement appelé *Garradin*)</small> signifie *coopérer* en *Māori*. C'est un logiciel de gestion d'association, libre, simple et efficace, développé depuis 2012. Son but est de&nbsp;:

* **réduire le temps** passé sur les tâches administratives&nbsp;;
* re-**donner de l'autonomie aux adhérent⋅e⋅s** dans la gestion de leurs données&nbsp;;
* **simplifier la gestion administrative** de l'association, pour inciter à y participer&nbsp;;
* **minimiser le nombre de logiciels à installer et maintenir** en intégrant les outils habituels.
	
Pour en savoir plus : [voir les principales fonctionnalités](#features).
</div>

<div id="warn">
	<p><strong>Attention&nbsp;: ce site est dédié au logiciel libre Paheko.</strong><br />
	Son installation, sur un serveur ou sur un ordinateur personnel, nécessite quelques compétences techniques.</p>
	<p>Si votre association n'a pas ces compétences, nous recommandons l'utilisation de notre service d'hébergement&nbsp;:<br /><strong class="cloud"><a href="https://paheko.cloud/" target="_blank"><img src="./icon.png" alt="" /> Paheko.cloud</a></strong> 
		<small>(<strong>Essai gratuit</strong>, puis contribution à prix libre, à partir de 5&nbsp;€ par an)</small>
</div>
<nav id="gnav">

* [Guides d'installation](/wiki/?name=Installation)
* [Documentation](/wiki/?name=Documentation)
* [Entraide](/wiki/?name=Entraide)
* <a href="https://paheko.cloud/" target="_blank">Essayer gratuitement sur &nbsp; <b><img src="./icon.png" alt="" /> Paheko.cloud</b></a>

<ul id="news">
	<li><a href="$ROOT/wiki/?name=Changelog">Nouveautés</a></li>
	<li><a href="$ROOT/uvlist">Anciennes versions</a></li>
</ul>

</nav>

<p id="give"><a href="https://kd2.org/soutien.html" target="_blank">Soutenir Paheko en effectuant un don :-)</a></p>

<form method="GET" action="$ROOT/wiki" id="search_all" onsubmit="var t = this.querySelector('[type=radio]:checked'); this.querySelector('[name=s]').name=t.dataset.name; this.action=t.dataset.action; this.target=t.dataset.target;">
<fieldset class="searchForm searchFormWiki">
	<legend>Rechercher</legend>
	<input type="search" name="s" size="40" value="" />
	<label><input type="radio" name="t" value="" data-name="s" data-action="/paheko/wiki" data-target="" checked="checked" /> Chercher dans la documentation technique</label>
	<label><input type="radio" name="t" value="1" data-action="https://paheko.cloud/search" data-name="search" data-target="_blank" /> Chercher dans l'aide utilisateur</label>
	<input type="submit" value="Rechercher" />
</fieldset>
</form>

<script type="text/javascript">
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

		var links = `<div id="download">
			<h2>Télécharger&nbsp;:</h2>

			<nav>`;

		if ('tar.gz' in last) {
			links += `
			<div>

				<h3><a href="$ROOT/uv/${last['tar.gz'].name}"><img src="data:image/svg+xml;base64,PHN2ZwogIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICB3aWR0aD0iMjQiCiAgaGVpZ2h0PSIyNCIKICB2aWV3Qm94PSIwIDAgMjQgMjQiCiAgZmlsbD0ibm9uZSIKICBzdHJva2U9ImN1cnJlbnRDb2xvciIKICBzdHJva2Utd2lkdGg9IjIiCiAgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIgogIHN0cm9rZS1saW5lam9pbj0icm91bmQiCj4KICA8cmVjdCB4PSIyIiB5PSIyIiB3aWR0aD0iMjAiIGhlaWdodD0iOCIgcng9IjIiIHJ5PSIyIiAvPgogIDxyZWN0IHg9IjIiIHk9IjE0IiB3aWR0aD0iMjAiIGhlaWdodD0iOCIgcng9IjIiIHJ5PSIyIiAvPgogIDxsaW5lIHgxPSI2IiB5MT0iNiIgeDI9IjYuMDEiIHkyPSI2IiAvPgogIDxsaW5lIHgxPSI2IiB5MT0iMTgiIHgyPSI2LjAxIiB5Mj0iMTgiIC8+Cjwvc3ZnPgo=" alt="" /><span>Serveur</span></a></h3>
				<p>pour auto-hébergement<br />
					<em>(.tar.gz, ${last['tar.gz'].human_size})</em><br />
					<small><a href="$ROOT/wiki/?name=Installation">Guides d'installation</a></small>
				</p>

			</div>`;
		}

		if ('deb' in last) {
			links += `
			<div>

				<h4><a href="$ROOT/uv/${last['deb'].name}"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGoAAAB8AgMAAAD8wM2CAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAAlwSFlzAAAF3gAABd4BiRluJgAAAAxQTFRFAAAAR3BMAAAAAAAAoOHXxQAAAAR0Uk5T/QBQozL7B+YAAAPySURBVEjHndc9aBxHFADgd7u64hTv4eYgxRVulijYpQURpDgIKVwILtbN7GAt9jquEl/gItUOl5gUhgjUbNKkMBhhkDE2rhOY2LhwYVvNgiEu0qhOwKiISSAzszPz3q7mhIlKfczuzJv3swds4d8Y3tEu/fNyb4FxCQBl2HJFkIRtpI0spGYIVkM2qS0JWV5bFLKiNnwhsZm1KmAja2nA5ta6AZMnmCXonWDJCdY5btxZ9D8tDtkpgD5fYKVUFzQKWqT+P9ARD5yh80UB1b/jkPHkh1346dTPwXXJ8gG8Wn6NF0hsuXMaXkXDsMVnYBifCxucgXMwxIsn9hz69z6G00F7LP+8MYXdoD355qvP2KNx0KTOaP4XqOAcz6XSZmnAbrrMT4/bt6Y8+WLbyrCSiH2it7IqMEFpzqtnTcsiaPNfGf9bZ3cSsN/fvinFa0zQxtm/3GPbE0wmas/VXnpqnxDIszslKyqO1YmWwWRtMzFlkbZNxEzGlSnRbtvGEbu0V5d20rZZx7eLqG2yh22mZdyFWPiNehNud5nf6BgPWmEddps2cinLfcl7k0DqN25YhiXpe5ozgS1Aus04y7GtzN1mnBVoM9ebnI2wVRWxrF/gbI7tL49tP3Amsb9P4IO6Pp3B+wNMgKSuCWscditMnGhuDuFtRufLzLzdWhbdZKThH5jma23S6TAyKIbmRNZEvzG/VkwkrOVLPTrrVkzkrY0/wvGl4n6e2myYMjLQlkxgrI0OBi0rvcnbeHR1z0vmdq3BrGzZwFkWj9gim9CwKDtPLI++a1vqrOg8JsbV2dFmnd8a66jNG8Z18/UmW+sOTOOqDaKdxrrbZB3E6411V6Q3VR73qSX7P0piA2pnISoGaF1qfwCs+LisTSNid1W1vOfuIVOdCu9BxHrouvublGyOObGhO0V8aE2oiOJDv9adIn5q7ZrOLX/x67rikwvGcujrHPFJuK4rfvWGse39Uh8jQlMVX101dst2B/fQW2aOf27syBbuqrdCveCisdQWT+KNv6nYZWJj33wu1BEgJvwMri2r8H2Zv4vaWBcNO4W1bWJ+o89syFNl37tKrl+cuQDtoOV2L8LZNWWf2qqzQSuc5Wg8bc4CJnb9M/1Z6LqjRjwKbxvKXPpdtd/RZJ+Z/3zQ+5z6dBSHYxA+v9T1X8dvzBcqLlfId7LEj2ihY30RS4R+YO8wapxaZmolL1sfoMY2zHXzfstS+0h9R9N+ecy469fX45KajkXuZ8CmQQ5v1bfNA6ltC2fHpn5ntob3cUTmynTNxqzOCdGYfw8PmWtpWzqPGjP1yS8u58WHm73WfN/3xfnQ7jv426l8h99V/wEOtd7r5KQmrQAAAABJRU5ErkJggg==" alt="" /><span>Linux</span></a></h4>
				<p>hors-ligne, pour ordinateur<br />
					<em>(.deb, ${last['deb'].human_size})</em><br />
					<small><a href="$ROOT/wiki/?name=Fonctionnement+hors-ligne">Guide d'installation</a></small>
				</p>

			</div>`;
		}

		if ('exe' in last) {
			links += `
			<div>

				<h4><a href="$ROOT/uv/${last['exe'].name}"><img src="data:image/svg+xml;base64,PHN2ZyBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAyNCAyNCIgdmlld0JveD0iMCAwIDI0IDI0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Im0yMiAyLTEwLjggMS42djhsMTAuOC0uMXptLTExLjggMTAuNS04LjItLjF2Ni44bDguMSAxLjF6bS04LjItNy43djYuOGg4LjF2LTcuOXptOS4xIDcuN3Y3LjlsMTAuOSAxLjZ2LTkuNHoiLz48L3N2Zz4=" alt="" /><span>Windows</span></a></h4>
				<p>hors-ligne, pour ordinateur<br />
					<em>(.exe, ${last['exe'].human_size})</em><br />
					<small><a href="$ROOT/wiki/?name=Installation/Windows">Guide d'installation</a></small>
				</p>

			</div>`;
		}

		links += `
			</nav>

		</div>`;

		document.querySelector('#news').insertAdjacentHTML('afterend', links);
	});
});
</script>

<a name="features"></a>

## C'est quoi ?

* **100% libre :** placé sous la licence [AGPL v3](https://www.gnu.org/licenses/why-affero-gpl.fr.html).
* Gestion des **adhérent⋅e⋅s** : fiches de membre personnalisables, recherches personnalisées…
* Gestion des **cotisations** et **activités** : suivi des adhérent⋅e⋅s à jour, des paiements en attente, **rappels automatiques** de cotisation par e-mail, etc.
* Envoi de **newsletters** avec suivi des adresses e-mail invalides
* **Comptabilité** puissante (à double entrée), **simple à utiliser par les débutant⋅e⋅s** : recettes, dépenses, suivi des dettes et créances, bilan et compte de résultat annuel, **comptabilité analytique**, export PDF, **reçus fiscaux**, etc.
* Stockage et **partage** de **documents** : édition collaborative, synchronisation des fichiers sur un ordinateur, etc.
* Gestion du **site web** de l'association
* Comptabilisation du **temps bénévole** et sa **valorisation**
* Gestion de la **caisse informatisée** d'un atelier ou d'une boutique
* Réservation de **créneaux et d'événements**
* **Conforme au RGPD** : export des données de l'adhérent⋅e, désabonnement des e-mails, chiffrement des mots de passe…

**<a href="http://paheko.cloud/a-propos" target="_blank">Présentation des fonctionnalités sur le site Paheko</a>**

## Dans quels buts ?

Le but est de permettre :

*  la gestion des __adhérent⋅e⋅s__ : ajout, modification, suppression, possibilité de choisir les informations présentes sur les fiches adhérent, envoi de mails collectifs aux adhérent⋅e⋅s
*  la tenue de la __comptabilité__ : avoir une gestion comptable complète à même de satisfaire un expert-comptable tout en restant à la portée de celles et ceux qui ne savent pas ce qu'est la comptabilité à double entrée, permettre la production des rapports et bilans annuels et de suivre au jour le jour le budget de l'association
*  la gestion des __cotisations__ et __activités__ : suivi des cotisations à jour, inscriptions et paiement des activités, rappels automatiques par e-mail, etc.
*  le travail __collaboratif__ et __collectif__ : gestion fine des droits d'accès aux fonctions, échange de mails entre membres…
*  la __simplification administrative__ : prise de notes en réunion, archivage et partage de fichiers (afin d'éliminer le besoin d'archiver les documents papier), etc.
*  la publication d'un __site web__ pour l'association, simple mais suffisamment flexible pour pouvoir adapter le fonctionnement à la plupart des besoins
*  l'__autonomisation des adhérents__ : possibilité de mettre à jour leurs informations par eux-même, ou de s'inscrire seul depuis un ordinateur ou un smartphone
*  la possibilité d'adapter aux besoins spécifiques de chaque association via des [__extensions__](wiki:Extensions).

* Fonctionnalités qu'il reste à implémenter : voir [la feuille de route (roadmap)](wiki:Roadmap).
* Paheko ne convient pas ? [Voir la liste des alternatives, libres ou propriétaires](wiki:Alternatives)

## Un seul logiciel

Paheko réunit en un seul outil les besoins suivants :

* gestion des membres : remplace Ciel Associations, EBP, Assoconnect ou Galette ;
* comptabilité : remplace Assoconnect, Odoo, Dolibarr, Grisbi, GNUcash, Sage, etc. ;
* gestion et partage de fichiers, remplace NextCloud, Google Drive ou Dropbox ;
* site web : remplace WordPress, Drupal, etc. ;
* suivi du bénévolat : remplace Bénévalibre et les tableaux Excel.

## Documentation et entraide

* D'abord lire la [documentation](/wiki/?name=Documentation) et notamment la [foire aux questions](wiki:FAQ)
* Voir la page [Entraide](/wiki/?name=Entraide) pour accéder aux listes de discussion et au salon de discussion IRC

## Participer

Tout coup de main est le bienvenu, pas besoin d'avoir des connaissances techniques ! Nous avons un [guide de contribution](wiki:Contribuer) pour vous aider à voir comment vous pouvez participer à Paheko :)

### Développement

Paheko est un logiciel libre, développé en PHP, utilisant la base de données SQLite, et avec une interface utilisant HTML, CSS et un peu de Javascript.

Nous acceptons les contributions (plugins, patch, code, tickets, etc.) avec plaisir, consultez la [documentation développeur⋅euse](wiki:Documentation développeur) pour découvrir comment vous pouvez contribuer.
