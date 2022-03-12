{include file="admin/_head.tpl" current="config" title="Fiche des membres"}

{include file="admin/config/_menu.tpl" current="fields"}

<nav class="tabs">
	{linkbutton shape="plus" label="Ajouter un champ" href="new.php"}
</nav>

{if $_GET.msg == 'SAVED'}
	<p class="block confirm">
		L'ordre a bien été enregistré.
	</p>
{elseif isset($status) && $status == 'ADDED'}
	<p class="block alert">
		Le champ a été ajouté à la fin de la liste. Pour vérifier et sauvegarder les modifications de la fiche membre cliquer sur le bouton «&nbsp;Vérifier les changements&nbsp;» en base de page.
	</p>
{/if}

{form_errors}

<form method="post" action="{$self_url_no_qs}">
	<table class="list">
		<thead>
			<tr>
				<td>Ordre</td>
				<th>Libellé</th>
				<td>Liste des membres</td>
				<td></td>
			</tr>
		</thead>
		<tbody>
		{foreach from=$fields item="field"}
			<tr>
				<td>
					{button shape="menu" title="Cliquer, glisser et déposer pour modifier l'ordre"}
					<input type="hidden" name="sort_order[]" value="{$field.name}" />
				</td>
				<th>{$field.label}</th>
				<td>{if $field.list_row}Oui{else}Non{/if}</td>
				<td class="actions">
					{if !$field.system || ($field.system && !($field.system | $field::PRESET))}
						{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$field.id}
					{/if}
					{linkbutton shape="edit" label="Modifier" href="edit.php?id=%d"|args:$field.id}
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>
</form>

<p class="help">
	Cliquer et glisser-déposer sur une ligne pour en changer l'ordre.
</p>

<script type="text/javascript" src="{$admin_url}static/scripts/dragdrop-table.js"></script>

{include file="admin/_foot.tpl"}