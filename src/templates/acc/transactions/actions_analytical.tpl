{include file="admin/_head.tpl" title="Ajouter/supprimer des écritures à un projet" current="acc/accounts"}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Affecter {$count} écritures sélectionnées à un projet</legend>
		<dl>
			<dd>
				{input type="select" name="id_analytical" options=$analytical_accounts label="Projet à utiliser" help="Pour retirer les écritures de leur projet actuellement affecté, sélectionner simplement « Aucun projet »."}
			</dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_actions"}
		<input type="hidden" name="action" value="add" />
		{button type="submit" name="change_analytical" label="Modifier les écritures" shape="right" class="main"}

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
