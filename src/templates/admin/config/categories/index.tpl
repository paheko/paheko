{include file="admin/_head.tpl" title="Catégories de membres" current="config"}

{include file="admin/config/_menu.tpl" current="categories"}

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
					{if $cat.id != $user.category_id}
						{linkbutton shape="delete" label="Supprimer" href="supprimer.php?id=%d"|args:$cat.id}
					{/if}
					{linkbutton shape="edit" label="Modifier" href="modifier.php?id=%d"|args:$cat.id}
					{linkbutton shape="users" label="Liste des membres" href="!membres/?cat=%d"|args:$cat.id}
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


{include file="admin/_foot.tpl"}