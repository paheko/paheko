{include file="_head.tpl" current="users"}

{include file="users/_nav.tpl" current="index"}

{if isset($_GET['sent'])}
<p class="block confirm">Le message a bien été envoyé.</p>
{/if}

{if $_GET.msg == 'DELETE'}
	<p class="block confirm">Le membre a été supprimé.</p>
{elseif $_GET.msg == 'DELETE_MULTI'}
	<p class="block confirm">Les membres sélectionnés ont été supprimés.</p>
{elseif $_GET.msg == 'DELETE_FILES'}
	<p class="block confirm">Les fichiers des membres sélectionnés ont été supprimés.</p>
{elseif $_GET.msg == 'CATEGORY_CHANGED'}
	<p class="block confirm">Les membres sélectionnés ont bien été changés de catégorie.</p>
{/if}

{if !empty($categories_list)}
	<fieldset class="shortFormRight">
		<legend>Filtrer par catégorie</legend>
		{dropdown value=$current_cat options=$categories_list title="Sélectionner une catégorie de membres"}
	</fieldset>
{/if}

<form method="get" action="search.php" class="shortFormLeft" data-focus="1">
	<fieldset>
		<legend>Rechercher un membre</legend>
		{input type="text" name="qt" placeholder="Nom, numéro, ou adresse e-mail"}
		{input type="hidden" name="id_category" default=$current_cat}
		{button type="submit" name="" title="Chercher" shape="search"}
	</fieldset>
</form>

<form method="post" action="action.php" class="users-list" target="_dialog">

{if $list->count()}
	{$list->getHTMLPagination()|raw}

	{include file="common/dynamic_list_head.tpl" check=$can_check}

	{foreach from=$list->iterate() item="row"}
		<?php $url = sprintf('details.php?id=%d&list_category=%d', $row->_user_id, $current_cat); ?>
		<tr>
			{if $can_check}
				<td class="check">{input type="checkbox" name="selected[]" value=$row._user_id}</td>
			{/if}
			{foreach from=$list->getHeaderColumns() key="key" item="value"}
				<?php $value = $row->$key; ?>
				{if $key == 'number'}
					<td class="num">
						{link href=$url label=$value}
					</td>
				{elseif $key == 'identity'}
					<th>{link href=$url label=$value}</th>
				{elseif $key == 'id_parent'}
					<td>
						{if $value}
							{link href=$url label=$row._parent_name}
						{/if}
					</td>
				{elseif $key == 'is_parent'}
					<td>
						{if $value}
							Oui
						{/if}
					</td>
				{else}
					<td>
						{user_field name=$key value=$value user_id=$row._user_id files_href=$url}
					</td>
				{/if}
			{/foreach}

			<td class="actions">
				{linkbutton label="Fiche membre" shape="user" href=$url}
				{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
					{linkbutton label="Modifier" shape="edit" href="edit.php?id=%d&list_category=%d"|args:$row._user_id:$current_cat}
				{/if}
			</td>
		</tr>
	{/foreach}

	</tbody>

	{if $can_check}
		{include file="users/_list_actions.tpl" colspan=count($list->getHeaderColumns())+$can_check+1}
	{/if}

	</table>

	{$list->getHTMLPagination()|raw}
{else}
	<p class="block alert">
		Aucun membre trouvé.
	</p>
{/if}

</form>

{include file="_foot.tpl"}