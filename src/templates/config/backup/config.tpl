{include file="_head.tpl" title="Configurer les sauvegardes" current="config"}

{include file="config/_menu.tpl" current="backup"}

{include file="config/backup/_menu.tpl" current="config"}

{form_errors}

<form method="post" action="{$self_url_no_qs}">

<fieldset>
	<legend>Configuration de la sauvegarde automatique</legend>
	<p class="help">
		En activant cette option une sauvegarde sera automatiquement créée à chaque intervalle donné.
		Par exemple en activant une sauvegarde hebdomadaire, une copie des données sera réalisée
		une fois par semaine, sauf si aucune modification n'a été effectuée sur les données
		ou que personne ne s'est connecté.
	</p>
	<dl>
		{input type="select" name="backup_frequency" source=$config label="Fréquence de sauvegarde" required=true options=$frequencies}
		{input type="number" step="1" min="0" max="50" name="backup_limit" source=$config label="Nombre de sauvegardes conservées" required=true options=$frequencies help="Par exemple avec une fréquence mensuelle, en indiquant de conserver 12 sauvegardes, vous pourrez garder un an d'historique de sauvegardes." default=1}
		<dd class="alert block">
			<strong>Attention :</strong> si vous choisissez un nombre important et un intervalle réduit,
			l'espace disque occupé par vos sauvegardes va rapidement augmenter.
		</dd>
	</dl>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="config" label="Enregistrer" shape="right" class="main"}
	</p>
</fieldset>


<div class="help block">
	<p>
		Attention, la sauvegarde automatique permet uniquement de revenir à un état antérieur, mais ne prévient pas de la perte des données&nbsp;!<br />
		Pour cela, il est recommandé de faire des sauvegardes manuelles en téléchargeant une copie des données sur votre ordinateur.
	</p>
	{if FILE_STORAGE_BACKEND !== 'SQLite'}
		<p>La sauvegarde automatique ne concerne que la base de données, mais pas les documents, fichiers joints aux écritures ou aux membres, ni le contenu du site web.</p>
	{/if}
</div>
</form>

{include file="_foot.tpl"}