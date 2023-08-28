{include file="_head.tpl" title="Nommer une version" current=null}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Nommer une version</legend>
		<dl>
			<dt>Nom actuel</dt>
			<dd><input type="text" disabled="disabled" value="{$version.name}" /></dd>
			{input type="text" name="new_name" required="required" label="Nouveau nom" default=$version.name}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="rename" value=$version.version label="Renommer" shape="right" class="main"}
		</p>
	</fieldset>
</form>

{include file="_foot.tpl"}