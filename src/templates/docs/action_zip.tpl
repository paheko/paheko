{include file="_head.tpl" title="Télécharger des fichiers" current="acc/docs"}

{form_errors}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Télécharger {$count} fichiers en ZIP…</legend>

		<p class="help">
			Vous allez télécharger un fichier ZIP de {$size|size_in_bytes}.
		</p>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="zip" label="Télécharger" shape="right" class="main"}

		{if isset($extra)}
			{foreach from=$extra key="key" item="value"}
				{if is_array($value)}
					{foreach from=$value key="subkey" item="subvalue"}
						<input type="hidden" name="{$key}[{$subkey}]" value="{$subvalue}" />
					{/foreach}
				{else}
					<input type="hidden" name="{$key}" value="{$value}" />
				{/if}
			{/foreach}
		{/if}
	</p>

</form>

<script type="text/javascript">
document.forms[0].onsubmit = () => window.parent.g.closeDialog();
</script>

{include file="_foot.tpl"}
