{include file="_head.tpl" title=$title current="web"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Informations générales</legend>
		<dl>
			{input type="text" name="title" required=true label="Titre"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="create" label="Créer" shape="plus" class="main"}
	</p>

</form>


{include file="_foot.tpl"}