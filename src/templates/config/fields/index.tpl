{include file="_head.tpl" current="config" title="Fiche de membre"}

{include file="config/_menu.tpl" current="users" sub_current="fields"}

{if $_GET.msg == 'SAVED_ORDER'}
	<p class="block confirm">
		L'ordre a bien été enregistré.
	</p>
{elseif $_GET.msg == 'SAVED'}
	<p class="block confirm">
		Les modifications ont bien été enregistrées.
	</p>
{elseif $_GET.msg == 'DELETED'}
	<p class="block confirm">
		Le champ a bien été supprimé.
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
				<td>Obligatoire&nbsp;?</td>
				<td>Accès membre</td>
				<td>Accès gestion</td>
				<td></td>
			</tr>
		</thead>
		<tbody>
		{foreach from=$fields item="field"}
			<tr>
				<td>
					<span class="draggable" title="Cliquer, glisser et déposer pour modifier l'ordre">{button shape="menu"}</span>
					<input type="hidden" name="sort_order[]" value="{$field.name}" />
				</td>
				<th>{$field.label}</th>
				<td>{if $field.list_table || $field->isName()}Oui{/if}</td>
				<td>{if $field.required}Obligatoire{/if}</td>
				<td>
					{if $field.user_access_level === $session::ACCESS_NONE}
						{icon shape="eye-off" title="Caché"}
					{elseif $field.user_access_level === $session::ACCESS_READ}
						{icon shape="eye" title="Visible"}
					{else}
						{icon shape="eye" title="Visible"}
						{icon shape="edit" title="Modifiable"}
					{/if}
				</td>
				<td>
					<span class="permissions">{display_permissions section="users" level=$field.management_access_level}</span>
					{if $field.management_access_level === $session::ACCESS_READ}
						Lecture
					{elseif $field.management_access_level === $session::ACCESS_WRITE}
						Écriture
					{elseif $field.management_access_level === $session::ACCESS_ADMIN}
						Administration
					{/if}
				</td>
				<td class="actions">
					{if $field->canDelete()}
						{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$field.id target="_dialog"}
					{/if}
					{linkbutton shape="edit" label="Modifier" href="edit.php?id=%d"|args:$field.id target="_dialog"}
					{button shape="up" title="Déplacer vers le haut" class="up"}
					{button shape="down" title="Déplacer vers le bas" class="down"}
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>

	<p class="help">
		Cliquer et glisser-déposer sur une ligne pour en changer l'ordre.
	</p>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer l'ordre" shape="right"}
	</p>
</form>

<script type="text/javascript" src="{$admin_url}static/scripts/dragdrop-table.js"></script>

{include file="_foot.tpl"}