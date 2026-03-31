{include file="_head.tpl" title="Édition de fichier" custom_js="code_editor.js"}

{form_errors}

<form method="post" action="{$self_url}" class="editor">
	<nav class="buttons">
		<h3>{$file.name}</h3>
		<p class="block alert modified">Modifié</p>
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer"}
	</nav>
	<p>
		{input type="textarea" name="content" cols="90" rows="50" default=$content autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"}
	</p>
	<div id="editor"></div>
	<div id="help"></div>
</form>

<script type="text/javascript">
const lang = {if $file}{$file->getHighlightLanguage()|json_encode|raw}{else}'html'{/if};
createCodeEditor(lang, '#f_content');
</script>

{include file="_foot.tpl"}
