{include file="admin/_head.tpl" title="Sauvegarder" current="config"}

{include file="admin/config/_menu.tpl" current="backup"}

{include file="admin/config/backup/_menu.tpl" current="save"}

{form_errors}

{if $ok}
	<p class="block confirm">
		{if $ok == 'create'}Une nouvelle sauvegarde a été créée.
		{elseif $ok == 'config'}La configuration a bien été enregistrée.
		{/if}
	</p>
{/if}

<form method="post" action="{$self_url_no_qs}">

<fieldset>
	<legend>Téléchargement d'une sauvegarde</legend>
	<p class="help">
		Info : la base de données fait actuellement {$db_size|size_in_bytes}.
		{if FILE_STORAGE_BACKEND == 'SQLite'} (Dont {$files_size|size_in_bytes} pour les documents.){/if}
	</p>
	<p class="submit">
		{csrf_field key="backup_download"}
		{button type="submit" name="download" label="Télécharger une copie de la base de données sur mon ordinateur" shape="download" class="main"}
	</p>
</fieldset>

<fieldset>
	<legend>Téléchargement des fichiers</legend>
	<p class="help">
		Les documents font {$files_size|size_in_bytes}.
	</p>
	{if $files_size > 0}
	<p class="submit">
		{csrf_field key="files_download"}
		{button type="submit" name="download_files" label="Télécharger une archive ZIP des documents sur mon ordinateur" shape="download" class="main"}
	</p>
	{/if}
</fieldset>

</form>

<form method="post" action="{$self_url_no_qs}">

<fieldset>
	<legend>Sauvegarde manuelle de la base de données</legend>
	<p class="help">
		Cette sauvegarde sera enregistrée sur le serveur et pourra être restaurée plus tard.<br />
		Cette sauvegarde ne concerne que la base de données, mais pas les documents, fichiers joints aux écritures ou aux membres, ni le contenu du site web.
	</p>
	<p class="submit">
		{csrf_field key="backup_create"}
		{button type="submit" name="create" label="Créer une nouvelle sauvegarde" shape="right" class="main"}
	</p>
</fieldset>

</form>

<form method="post" action="{$self_url_no_qs}">

<fieldset>
	<legend>Configuration de la sauvegarde automatique</legend>
	<p class="help">
		En activant cette option une sauvegarde sera automatiquement créée à chaque intervalle donné.
		Par exemple en activant une sauvegarde hebdomadaire, une copie des données sera réalisée
		une fois par semaine, sauf si aucune modification n'a été effectuée sur les données
		ou que personne ne s'est connecté.
	</p>
	<p class="alert block">
		Attention, la sauvegarde automatique permet uniquement de revenir à un état antérieur, mais ne prévient pas de la perte des données&nbsp;! Pour cela, il est recommandé de faire des sauvegardes manuelles en téléchargeant une copie des données sur votre ordinateur.
		{if FILE_STORAGE_BACKEND != 'SQLite'}<br /><br />
		La sauvegarde automatique ne concerne que la base de données, mais pas les documents, fichiers joints aux écritures ou aux membres, ni le contenu du site web.{/if}
	</p>
	<dl>
		<dt><label for="f_frequency">Intervalle de sauvegarde</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
		<dd>
			<select name="frequence_sauvegardes" required="required" id="f_frequency">
				<option value="0"{form_field name=frequence_sauvegardes data=$config selected=0}>Aucun — les sauvegardes automatiques sont désactivées</option>
				<option value="1"{form_field name=frequence_sauvegardes data=$config selected=1}>Quotidien, tous les jours</option>
				<option value="7"{form_field name=frequence_sauvegardes data=$config selected=7}>Hebdomadaire, tous les 7 jours</option>
				<option value="15"{form_field name=frequence_sauvegardes data=$config selected=15}>Bimensuel, tous les 15 jours</option>
				<option value="30"{form_field name=frequence_sauvegardes data=$config selected=30}>Mensuel</option>
				<option value="90"{form_field name=frequence_sauvegardes data=$config selected=90}>Trimestriel</option>
				<option value="365{form_field name=frequence_sauvegardes data=$config selected=365}">Annuel</option>
			</select>
		</dd>
		<dt><label for="f_max_backups">Nombre de sauvegardes conservées</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
		<dd class="help">
			Par exemple avec l'intervalle mensuel, en indiquant de conserver 12 sauvegardes,
			vous pourrez garder un an d'historique de sauvegardes.
		</dd>
		<dd class="help">
			<strong>Attention :</strong> si vous choisissez un nombre important et un intervalle réduit,
			l'espace disque occupé par vos sauvegardes va rapidement augmenter.
		</dd>
		<dd><input type="number" name="nombre_sauvegardes" value="{form_field name=nombre_sauvegardes data=$config}" if="f_max_backups" min="1" max="50" required="required" /></dd>
	</dl>
	<p class="submit">
		{csrf_field key="backup_config"}
		{button type="submit" name="config" label="Enregistrer" shape="right" class="main"}
	</p>
</fieldset>

</form>

{include file="admin/_foot.tpl"}