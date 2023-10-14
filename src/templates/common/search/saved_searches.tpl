{include file="_head.tpl" title="Recherches enregistrées" current=$target}

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

{if $mode == 'edit'}
	{form_errors}

	<form method="post" action="{$self_url}" data-focus="1">
		<fieldset>
			<legend>Modifier une recherche enregistrée</legend>
			<dl>
				{input type="text" name="label" label="Intitulé" required=1 source=$search}
				<dt>Statut</dt>
				<?php $public = (int) (null === $search->id_user); ?>
				{input type="radio" name="public" value="0" default=$public label="Recherche privée" help="Visible seulement par moi-même"}
				{if $session->canAccess($access_section, $session::ACCESS_WRITE)}
					{input type="radio" name="public" value="1" default=$public label="Recherche publique" help="Visible et exécutable par tous les membres ayant accès à la gestion %s"|args:$target}
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
			</dl>
		</fieldset>

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
		</p>
	</form>
{elseif $mode == 'delete'}

	{include file="common/delete_form.tpl"
		legend="Supprimer cette recherche enregistrée ?"
		warning="Êtes-vous sûr de vouloir supprimer la recherche enregistrée « %s » ?"|args:$search.label
		csrf_key=$csrf_key
	}

{elseif count($list) == 0}
	<p class="block alert">Aucune recherche enregistrée. <a href="{$search_url}">Faire une nouvelle recherche</a></p>
{else}
	<table class="list">
		<thead>
			<tr>
				<th>Recherche</th>
				<th>Type</th>
				<th>Statut</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$list item="search"}
			<tr>
				<th><a href="{$search_url}?id={$search.id}">{$search.label}</a></th>
				<td>{if $search.type == $search::TYPE_JSON}Avancée{else}SQL{/if}</td>
				<td>{if !$search.id_user}Publique{else}Privée{/if}</td>
				<td class="actions">
					{linkbutton href="%s?id=%d"|args:$search_url,$search.id shape="search" label="Rechercher"}
					{if $search.id_user || $session->canAccess($access_section, $session::ACCESS_ADMIN)}
						{linkbutton href="?edit=%d"|args:$search.id shape="edit" label="Modifier"}
						{linkbutton href="?delete=%d"|args:$search.id shape="delete" label="Supprimer"}
					{/if}
				</td>
			</tr>
			{/foreach}
		</tbody>
	</table>
{/if}

{include file="_foot.tpl"}