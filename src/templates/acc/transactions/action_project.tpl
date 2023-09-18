{include file="_head.tpl" title="Ajouter/supprimer des écritures à un projet" current="acc/accounts"}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Affecter {$count} écritures sélectionnées à un projet</legend>
		<dl>
			{input type="select" name="id_project" options=$projects label="Projet à utiliser" help="Pour retirer les écritures de leur projet actuellement affecté, sélectionner « Aucun projet »." default_empty="— Aucun projet —"}
			{input type="checkbox" name="apply_lines" value="1" default="1" checked=1 label="Appliquer à toutes les lignes des écritures"}
			<dd class="help">Si décoché, alors seules les lignes sélectionnées seront modifiées. Si coché, toutes les lignes des écritures sélectionnées seront modifiées. Laisser coché en cas de doute.</dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_actions"}
		<input type="hidden" name="action" value="add" />
		{button type="submit" name="change_project" label="Modifier les écritures" shape="right" class="main"}

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

{include file="_foot.tpl"}
