{include file="_head.tpl" title="Catégories de membres" current="config"}

{include file="config/_menu.tpl" current="users" sub_current="categories"}

<table class="list">
	<thead>
		<th>Nom</th>
		<td class="num">Membres</td>
		<td>Droits</td>
		<td></td>
	</thead>
	<tbody>
		{foreach from=$list item="cat"}
			<tr>
				<th>{$cat.name}</th>
				<td class="num">{$cat.count}</td>
				<td class="permissions">
					{display_permissions permissions=$cat}
				</td>
				<td class="actions">
					{if $cat.id != $logged_user.id_category}
						{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$cat.id target="_dialog"}
					{/if}
					{linkbutton shape="edit" label="Modifier" href="edit.php?id=%d"|args:$cat.id}
					{linkbutton shape="users" label="Liste des membres" href="!users/?cat=%d"|args:$cat.id}
				</td>
			</tr>
		{/foreach}
	</tbody>
</table>

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Ajouter une catégorie</legend>
		<dl>
			{input type="text" name="name" label="Nom" required=true}
		</dl>

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="save" label="Ajouter" shape="right" class="main"}
		</p>
	</fieldset>

</form>


{include file="_foot.tpl"}