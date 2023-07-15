{include file="_head.tpl" title="Édition de fichier"}

{form_errors}

<form method="post" action="{$self_url}">
	<p>
		{input type="textarea" name="content" cols="90" rows="50" default=$content}
	</p>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer et fermer" shape="right" class="main"}
	</p>
</form>

<script type="text/javascript" src="{$admin_url}static/scripts/code_editor.js?{$version_hash}"></script>

{include file="_foot.tpl"}
