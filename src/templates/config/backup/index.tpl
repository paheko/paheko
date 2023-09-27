{include file="_head.tpl" title="Sauvegarder" current="config"}

{include file="config/_menu.tpl" current="backup"}

{include file="config/backup/_menu.tpl" current="index"}

{if $_GET.msg == 'BACKUP_CREATED'}
	<p class="block confirm">
		Une nouvelle sauvegarde a été créée.
	</p>
{/if}

{if !$config.backup_frequency}
<div class="alert block">
	<p>Les sauvegardes automatiques sont désactivées. Il est recommandé de les activer pour pouvoir revenir en arrière en cas de problème majeur.</p>
	<p>Attention&nbsp;: cela ne dispense pas de réaliser des sauvegardes régulières sur votre ordinateur.</p>
	<p>{linkbutton shape="settings" href="auto.php" label="Configurer les sauvegardes automatiques"}</p>
</div>
{/if}

<section class="large">
	<form method="post" action="" data-disable-progress="1">
		<article>
			<h2>Sauvegarder la base de données sur mon ordinateur</h2>
			<h3>En cas de problème sur le serveur (plantage, dysfonctionnement du disque dur, incendie, etc.) vous pourriez perdre vos données.</h3>
			<p>Il est donc recommandé de réaliser régulièrement des sauvegardes et de les conserver sur votre ordinateur ou sur une clé USB&nbsp;!
			</p>
			<p>
				Cliquez sur le bouton ci-dessous pour télécharger une copie de la base de données et enregistrez-la ensuite sur votre ordinateur ou une clé USB&nbsp;:
			</p>
			<p class="submit">
				{csrf_field key=$csrf_key}
				{button type="submit" name="download" label="Télécharger la base de données" shape="download"} ({$db_size|size_in_bytes})
			</p>
		</article>

		<article>
			<h2>Créer une nouvelle sauvegarde de la base de données sur le serveur</h2>
			<p>Cette sauvegarde sera enregistrée sur le serveur et pourra être restaurée plus tard.</p>
			<p>Conseillé par exemple avant de réaliser une opération importante, pour pouvoir revenir en arrière.</p>
			{if FILE_STORAGE_BACKEND !== 'SQLite'}
				<p><strong>Attention&nbsp;:</strong> seule la base de données est sauvegardées, pas les documents et fichiers joints.</p>
			{/if}
			<p class="submit">
				{csrf_field key=$csrf_key}
				{button type="submit" name="create" label="Créer une nouvelle sauvegarde" shape="plus"}
			</p>
		</article>

		<article>
			<h2>Télécharger les fichiers sur mon ordinateur</h2>
			<p>Permet de télécharger un fichier ZIP contenant tous les fichiers (hors base de données et sauvegardes de la base de données)&nbsp;: documents, logo, fichiers joints aux écritures, aux fiches de membres, et aux pages du site web.</p>
			{if $files_size > 1024*1024*100}
				<p class="alert block">
					Ce téléchargement de <strong>{$files_size|size_in_bytes}</strong> peut prendre plusieurs minutes.<br />
					Veillez à utiliser une bonne connexion internet.
				</p>
			{/if}
			<p class="submit">
				{csrf_field key=$csrf_key}
				{button type="submit" name="zip" label="Télécharger une archive ZIP de tous les documents" shape="download"} ({$files_size|size_in_bytes})
			</p>
		</article>
	</form>

	<article>
		<h2>Exporter les données comptables</h2>
		<p>Il est conseillé d'exporter les informations comptables (bilan, compte de résultat, grand livre et journal) pour archivage après la clôture de chaque exercice, et de les stocker sur un support pérenne (clé USB, carte mémoire, disque dur externe).</p>
		<p>Ils doivent être conservés 10 ans.</p>
		<p>
			{linkbutton shape="menu" label="Voir la liste des exercices" href="!acc/years/"}
		</p>
	</article>
</section>


{include file="_foot.tpl"}