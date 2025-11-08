{include file="_head.tpl" title="Sauvegardes automatiques" current="config"}

{include file="config/_menu.tpl" current="backup"}

{include file="config/backup/_menu.tpl" current="auto"}

{form_errors}

{if $_GET.msg === 'CONFIG_SAVED'}
	<p class="block confirm">
		La configuration des sauvegardes a bien été enregistrée.
	</p>
{/if}

<form method="post" action="{$self_url_no_qs}">

<div class="alert block">
	<p>
		Attention, la sauvegarde automatique permet uniquement de revenir à un état antérieur, mais ne prévient pas de la perte des données en cas de problème du serveur (crash, incendie, etc.)&nbsp;!<br />
		Pour cela, il est recommandé de télécharger régulièrement une copie de la base de données et des documents sur votre ordinateur.
	</p>
	<p>
		Une sauvegarde est réalisée uniquement s'il y a eu des modifications depuis la dernière sauvegarde.
	</p>
	{if FILE_STORAGE_BACKEND !== 'SQLite'}
		<p><strong>La sauvegarde automatique ne concerne que la base de données, mais pas les documents, fichiers joints aux écritures, aux membres et aux pages du site web.</strong></p>
	{/if}
</div>

<fieldset>
	<legend>Configuration de la sauvegarde automatique</legend>

	<dl>
		{input type="radio-btn" name="backup" value="none" default=$backup label="Désactiver les sauvegardes automatiques"}
		{input type="radio-btn" name="backup" value="auto" default=$backup label="Sauvegardes automatiques optimisées (conseillé)" help="Effectue jusqu'à 25 sauvegardes : 8 sauvegardes quotidiennes, puis 5 sauvegardes hebdomadaires, puis 12 sauvegardes mensuelles"}
		{input type="radio-btn" name="backup" value="custom" default=$backup label="Sauvegardes automatiques personnalisées" help="Permet de choisir la fréquence et le nombre de sauvegardes"}
	</dl>
</fieldset>

<fieldset class="custom-backup hidden">
	<legend>Fréquence des sauvegardes</legend>
	<dl>
		{input type="select" name="backup_frequency" source=$config label="Fréquence de sauvegarde" required=true options=$frequencies}
		{input type="number" step="1" min="0" max="50" name="backup_limit" source=$config label="Nombre de sauvegardes conservées" required=true options=$frequencies help="Par exemple avec une fréquence mensuelle, en indiquant de conserver 12 sauvegardes, vous pourrez garder un an d'historique de sauvegardes." default=1}
		<dd class="help">
			<strong>Attention :</strong> si vous choisissez un nombre important, l'espace disque occupé par vos sauvegardes peut rapidement augmenter.
		</dd>
	</dl>
</fieldset>

<p class="submit">
	{csrf_field key=$csrf_key}
	{button type="submit" name="config" label="Enregistrer" shape="right" class="main"}
</p>

</form>

<script type="text/javascript">
{literal}
function changeOption()
{
	var is_custom = document.forms[0].backup.value == 'custom';
	g.toggle('.custom-backup', is_custom);
}
changeOption();
$('input[name=backup]').forEach(e => e.onchange = changeOption);
{/literal}
</script>

{include file="_foot.tpl"}