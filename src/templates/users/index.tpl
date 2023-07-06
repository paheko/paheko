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

{if !empty($categories)}
	<fieldset class="shortFormRight">
		<legend>Filtrer par catégorie</legend>
		<nav class="dropdown">
			<ul>
				<li><a></a></li>
			{foreach from=$categories key="c" item="category"}
			<li class="{if $c === $current_cat}selected{/if}">
				<a href="?cat={$c}">
					<strong>{$category.label}</strong>
					<small>{{%n membre}{%n membres} n=$category.count}</small>
				</a>
			</li>
			{/foreach}
			</ul>
		</nav>
	</fieldset>
{/if}

<form method="get" action="search.php" class="shortFormLeft" data-focus="1">
	<fieldset>
		<legend>Rechercher un membre</legend>
		<input type="text" name="qt" value="" placeholder="Nom, numéro, ou adresse e-mail" />
		{button type="submit" name="" title="Chercher" shape="search"}
	</fieldset>
</form>

<form method="post" action="action.php" class="users-list" target="_dialog">

{if $list->count()}
	{$list->getHTMLPagination()|raw}

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
				{elseif $key == 'id_parent'}
					<td>
						{if $value}
							{link href="details.php?id=%d"|args:$value label=$row._parent_name}
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
						{display_dynamic_field key=$key value=$value user_id=$row._user_id files_href="details.php?id=%d"|args:$row._user_id}
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

	{$list->getHTMLPagination()|raw}
{else}
	<p class="block alert">
		Aucun membre trouvé.
	</p>
{/if}

</form>

{include file="_foot.tpl"}