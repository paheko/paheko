{include file="admin/_head.tpl" title="Ajouter/supprimer des écritures à un projet" current="acc/accounts"}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Déplacer {$count} fichiers</legend>
		<dl>
			{input type="select" name="move_target" options=$directories label="Répertoire de destination"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="move" label="Déplacer les fichiers" shape="right" class="main"}

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

{include file="admin/_foot.tpl"}
