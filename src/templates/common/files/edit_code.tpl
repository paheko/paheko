{include file="admin/_head.tpl" title="Édition de fichier"}

<form method="post" action="{$self_url}">
	<p>
		{input type="textarea" name="content" cols="90" rows="50" default=$content}
	</p>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

<script type="text/javascript" src="{$admin_url}static/scripts/code_editor.js"></script>

{include file="admin/_foot.tpl"}
