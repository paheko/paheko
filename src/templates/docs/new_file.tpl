{include file="_head.tpl" title="Créer un fichier texte"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Créer un fichier texte</legend>
		<dl>
			{input type="text" minlength="1" name="name" required="required" label="Nom du fichier à créer"}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="create" label="Créer le fichier" shape="plus" class="main"}
		</p>
	</fieldset>
</form>

<script type="text/javascript">
var parent_path = {$parent|escape:'json'};
{literal}
var f = document.forms[0];
f.addEventListener('submit', () => {
	if (!window.parent.g.dialog) {
		return;
	}

	if (parent_path.match(/^modules/)) {
		return;
	}

	var ext = f['name'].value.lastIndexOf('.') === -1 ? '.md' : '';
	var url = "!docs/?f=" + encodeURIComponent(parent_path + '/' + f['name'].value + ext);
	window.parent.g.dialog_on_close = url;
});
{/literal}
</script>

{include file="_foot.tpl"}
