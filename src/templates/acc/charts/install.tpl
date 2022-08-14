{include file="_head.tpl" title="Importer un nouveau plan comptable" current="acc/charts"}

{include file="./_nav.tpl" current="install"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Installer un plan comptable</legend>
		<dl>
			{input type="select" name="code" label="Plan comptable" required=true options=$list}
		</dl>
	</fieldset>
	<p class="submit">
		{csrf_field key="acc_charts_install"}
		{button type="submit" name="install" label="Installer" shape="right" class="main"}
	</p>
</form>

{include file="_foot.tpl"}