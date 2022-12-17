{include file="_head.tpl" title="Liste des membres" current="users"}

{include file="users/_nav.tpl" current="index"}

{if isset($_GET['sent'])}
<p class="block confirm">Le message a bien été envoyé.</p>
{/if}

{if $_GET.msg == 'DELETE'}
	<p class="block confirm">Le membre a été supprimé.</p>
{/if}

{if !empty($categories)}
<form method="get" action="{$self_url}" class="shortFormRight">
	<fieldset>
		<legend>Filtrer par catégorie</legend>
		{input type="select" name="cat" onchange="this.form.submit();" options=$categories}
		<noscript>{button type="submit" name="" label="Filtrer" shape="right"}</noscript>
	</fieldset>
</form>
{/if}

<form method="get" action="search.php" class="shortFormLeft" data-focus="1">
	<fieldset>
		<legend>Rechercher un membre</legend>
		<input type="text" name="qt" value="" placeholder="Nom, numéro, ou adresse e-mail" />
		{button type="submit" name="" label="Chercher" shape="search"}
	</fieldset>
</form>

<form method="post" action="action.php" class="memberList">

{if $list->count()}
	{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}

	{include file="common/dynamic_list_head.tpl" check=$can_edit}

	{foreach from=$list->iterate() item="row"}
		<tr>
			{if $can_edit}
				<td class="check">{input type="checkbox" name="selected[]" value=$row._user_id}</td>
			{/if}
			{foreach from=$list->getHeaderColumns() key="key" item="value"}
				<?php $value = $row->$key; ?>
				{if $key == 'number'}
					<td class="num">
						{link href="details.php?id=%d"|args:$row._user_id label=$value}
					</td>
				{elseif $key == 'identity'}
					<th>{link href="details.php?id=%d"|args:$row._user_id label=$value}</th>
				{else}
					<td>
						{display_dynamic_field key=$key value=$value}
					</td>
				{/if}
			{/foreach}

			<td class="actions">
				{linkbutton label="Fiche membre" shape="user" href="details.php?id=%d"|args:$row._user_id}
				{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
					{linkbutton label="Modifier" shape="edit" href="edit.php?id=%d"|args:$row._user_id}
				{/if}
			</td>
		</tr>
	{/foreach}

	</tbody>

	{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
		{include file="users/_list_actions.tpl" colspan=count($list->getHeaderColumns())+$can_edit+1}
	{/if}

	</table>

	{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}
{else}
	<p class="block alert">
		Aucun membre trouvé.
	</p>
{/if}

</form>

{include file="_foot.tpl"}