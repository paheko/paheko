{include file="_head.tpl" title="Recherches enregistrées" current=$target}

{if !$dialog}
	{if $target == 'users'}
		{include file="users/_nav.tpl" current="saved_searches"}
	{else}
		<nav class="tabs">
			<ul>
				<li><a href="search.php">Recherche</a></li>
				<li class="current"><a href="saved_searches.php">Recherches enregistrées</a></li>
			</ul>
		</nav>
	{/if}
{/if}

{if $mode == 'edit'}
	{form_errors}

	<form method="post" action="{$self_url}" data-focus="1">
		<fieldset>
			<legend>Modifier une recherche enregistrée</legend>
			<dl>
				{input type="text" name="label" label="Intitulé" required=true source=$search}
				{input type="textarea" name="description" label="Commentaire" required=false source=$search cols=70 rows=5 help="Sera affiché au dessus du champ de recherche."}
				<dt>Statut</dt>
				<?php $public = (int) (null === $search->id_user); ?>
				{input type="radio" name="public" value="0" default=$public label="Recherche privée" help="Visible seulement par moi-même"}
				{if $session->canAccess($access_section, $session::ACCESS_WRITE)}
					{input type="radio" name="public" value="1" default=$public label="Recherche publique" help="Visible par tous les membres ayant accès à la gestion '%s'"|args:$target_label}
				{/if}
				<dt>Type</dt>
				<dd>
					{if $search.type == $search::TYPE_JSON}
						Avancée
					{elseif $search.type == $search::TYPE_SQL_UNPROTECTED}
						SQL non protégée
					{else}
						SQL
					{/if}
				</dd>
				{if $search.type == $search::TYPE_JSON}
					<dt>Nombre de résultats</dt>
					<dd>{$search->countResults(true)}</dd>
				{/if}
			</dl>
		</fieldset>

		<p class="submit">
			{csrf_field key=$csrf_key}
			<input type="hidden" name="content" value="{$search.content}" />
			<input type="hidden" name="type" value="{$search.type}" />
			{if $search->exists()}
				{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
				{button type="submit" name="duplicate" label="Dupliquer" shape="plus"}
				{linkbutton href="?delete=%d"|args:$search.id shape="delete" label="Supprimer"}
			{else}
				{button type="submit" name="save" label="Enregistrer cette nouvelle recherche" shape="right" class="main"}
			{/if}
		</p>
	</form>
{elseif $mode == 'delete'}

	{include file="common/delete_form.tpl"
		legend="Supprimer cette recherche enregistrée ?"
		warning="Êtes-vous sûr de vouloir supprimer la recherche enregistrée « %s » ?"|args:$search.label
		csrf_key=$csrf_key
	}

{elseif !$list->count()}
	<p class="block alert">Aucune recherche enregistrée. <a href="{$search_url}">Faire une nouvelle recherche</a></p>
{else}
	{include file="common/dynamic_list_head.tpl"}
			{foreach from=$list->iterate() item="search"}
			<tr>
				<th><a href="{$search_url}?id={$search.id}">{$search.label}</a></th>
				<td>{$search.type}</td>
				<td>{if !$search.id_user}Publique{else}Privée{/if}</td>
				<td>{$search.updated|relative_date}</td>
				<td class="actions">
					{linkbutton href="%s?id=%d"|args:$search_url:$search.id shape="search" label="Rechercher"}
					{if $search.id_user || $session->canAccess($access_section, $session::ACCESS_ADMIN)}
						{linkbutton href="?edit=%d"|args:$search.id shape="edit" label="Modifier" target="_dialog"}
						{linkbutton href="?delete=%d"|args:$search.id shape="delete" label="Supprimer" target="_dialog"}
					{/if}
				</td>
			</tr>
			{/foreach}
		</tbody>
	</table>

	{$list->getHTMLPagination()|raw}
{/if}

{include file="_foot.tpl"}