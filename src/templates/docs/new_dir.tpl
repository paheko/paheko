{include file="_head.tpl" title="Créer un dossier"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Créer un dossier</legend>
		<dl>
			{input type="text" minlength="1" name="name" required="required" label="Nom du dossier à créer"}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="create" label="Créer le dossier" shape="plus" class="main"}
		</p>
	</fieldset>
</form>

{include file="_foot.tpl"}
