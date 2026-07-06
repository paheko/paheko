{include file="_head.tpl" title="Site web — configuration" current="config"}

{include file="./_menu.tpl" current="index" sub_current="web"}

{if $_GET.msg == 'SAVED'}
	<p class="block confirm">
		La configuration a été enregistrée.
	</p>
{/if}

{form_errors}

<form method="post" action="{$self_url_no_qs}">
<fieldset>
	<legend>Site web intégré à Paheko</legend>
	{if $config.site_disabled}
		<div class="block alert">
			<h3>Le site web est désactivé</h3>
			<p>Les personnes se rendant à l'adresse <tt>{$www_url}</tt> seront redirigées vers le formulaire de connexion.</p>
			<p>Les membres connectés pourront toujours accéder aux pages et catégories dans le menu <strong>Site web</strong>.</p>
		</div>
		<dl>
			{input type="checkbox" name="site_disabled" value="0" label="Activer le site web"}
		</dl>
	{else}
		<div class="block confirm">
			<h3>Le site web est activé</h3>
			<p>Les personnes se rendant à l'adresse <tt>{$www_url}</tt> verront les pages et catégories publiées.</p>
		</div>
		<dl>
			{input type="checkbox" name="site_disabled" value="1" label="Désactiver le site web"}
		</dl>
	{/if}
</fieldset>

<fieldset>
	<legend>Site web externe</legend>
	<dl>
		{input type="url" name="org_web" source=$config label="Adresse du site web externe" help="Si votre association dispose d'un site web, cette sera utilisée dans les messages collectifs ou les reçus à la place du site intégré à Paheko."}
	</dl>
</fieldset>
<p>
	{csrf_field key=$csrf_key}
	{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
</p>
</form>

{include file="_foot.tpl"}