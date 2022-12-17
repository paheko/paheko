{include file="_head.tpl" title="Créer un document"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Créer un document</legend>
		<dl>
			{input type="text" minlength="1" name="name" required="required" label="Nom du document à créer"}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="create" label=$submit_name shape="plus" class="main"}
		</p>
	</fieldset>
</form>

{include file="_foot.tpl"}
