{include file="_head.tpl" title="Envoi de fichier"}

{form_errors}

<form method="post" action="{$self_url}" enctype="multipart/form-data" data-focus="1">
	<fieldset>
		<legend>Téléverser des fichiers</legend>
		<dl>
			{input type="file" name="file[]" multiple=true label="Fichiers à envoyer" data-enhanced=1}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="upload" label="Envoyer" shape="upload" class="main"}
		</p>
	</fieldset>
</form>

{include file="_foot.tpl"}
