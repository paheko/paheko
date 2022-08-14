{include file="_head.tpl" title="Envoi d'image"}

{form_errors}

<form method="post" action="{$self_url}" enctype="multipart/form-data" data-focus="1">
	<fieldset>
		<legend>Téléverser un fichier</legend>
		<dl>
			{input type="file" name="file" label="Fichier à envoyer" data-enhanced=1}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="upload" label="Envoyer" shape="upload" class="main"}
			{button type="submit" name="reset" label="Supprimer" shape="delete"}
		</p>
	</fieldset>
</form>

{include file="_foot.tpl"}
