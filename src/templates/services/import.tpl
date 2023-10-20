{include file="_head.tpl" title="Importer des inscriptions" current="users"}

{include file="services/_nav.tpl" current="import" service=null fee=null}

{form_errors}

{if $_GET.msg == 'OK'}
	<p class="block confirm">
		L'import s'est bien déroulé.
	</p>
{/if}

<form method="post" action="{$self_url}" enctype="multipart/form-data">

{if $csv->loaded()}

	{include file="common/_csv_match_columns.tpl"}

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="cancel" value="1" label="Annuler" shape="left"}
		{button type="submit" name="import" label="Importer" shape="right" class="main"}
	</p>

{else}

	<p class="help">
		Ce formulaire permet d'importer les inscriptions des membres aux activités.
	</p>

	<fieldset>
		<legend>Importer depuis un fichier</legend>
		<dl>
			{input type="file" name="file" label="Fichier à importer" required=true accept="csv"}
			{include file="common/_csv_help.tpl" csv=$csv}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="load" label="Charger le fichier" shape="right" class="main"}
	</p>
{/if}


</form>

{include file="_foot.tpl"}