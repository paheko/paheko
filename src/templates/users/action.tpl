{include file="_head.tpl" title="Action collective sur les membres" current="membres"}

{form_errors}

{if $action == 'delete'}
	{include file="common/delete_form.tpl"
		legend="Supprimer %d membres ?"|args:$count
		warning="Êtes-vous sûr de vouloir supprimer ces membres ?"
		alert="Cette action est irréversible et effacera toutes les données personnelles et les inscriptions aux activités de ces membres."
		extra=$extra
		info="Alternativement, il est aussi possible de déplacer les membres qui ne font plus partie de l'association dans une catégorie (par exemple \"Anciens membres\"), plutôt que de les supprimer."}
{elseif $action == 'delete_files'}
	{include file="common/delete_form.tpl"
		legend="Supprimer les fichiers de %d membres ?"|args:$count
		warning="Êtes-vous sûr de vouloir supprimer les fichiers de ces %d membres ?"|args:$count
		alert="Cette action est irréversible."
		extra=$extra}
{else}
	<form method="post" action="{$self_url}">
		{foreach from=$list item="id"}
			<input type="hidden" name="selected[]" value="{$id}" />
		{/foreach}

		<p class="block alert">
			{{%n membre sélectionné.}{%n membres sélectionnés} n=$count}
		</p>

		{if $action == 'move'}

			<fieldset>
				<legend>Changer la catégorie des membres sélectionnés</legend>
				<dl>
					{input type="select" name="new_category_id" label="Nouvelle catégorie" options=$categories required=true default_empty=""}
				</dl>
			</fieldset>

			<p class="submit">
				{csrf_field key=$csrf_key}
				<input type="hidden" name="action" value="move" />
				{button type="submit" name="confirm" label="Modifier la catégorie" shape="right" class="main"}
			</p>

		{/if}

	</form>
{/if}

{include file="_foot.tpl"}