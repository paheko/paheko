{include file="admin/_head.tpl" title="Créer un répertoire"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Créer un fichier texte</legend>
		<dl>
			{input type="text" minlength="1" size="60" name="name" required="required" label="Nom du fichier à créer"}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="create" label="Créer le fichier" shape="plus" class="main"}
		</p>
	</fieldset>
</form>

{include file="admin/_foot.tpl"}
