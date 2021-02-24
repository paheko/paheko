{include file="admin/_head.tpl" title="Envoi de fichier" is_popup=1 body_id="popup"}

<form method="post" action="{$self_url}" enctype="multipart/form-data">
	<fieldset>
		<legend>Téléverser un fichier</legend>
		<dl>
			{input type="file" name="file" required="required" label="Fichier à envoyer"}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="upload" label="Envoyer le fichier" shape="upload" class="main"}
		</p>
	</fieldset>
</form>

{include file="admin/_foot.tpl"}
