{include file="admin/_head.tpl" title="Recherches enregistrées" current=$target}

{if $target == 'membres'}
	{include file="admin/membres/_nav.tpl" current="recherches"}
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

	<form method="post" action="{$self_url}">
		<fieldset>
			<legend>Modifier une recherche enregistrée</legend>
			<dl>
				{input type="text" name="intitule" label="Intitulé" required=1 source=$recherche}
				<dt>Statut</dt>
				<?php $checked = (int)(bool)$recherche->id_membre; ?>
				{input type="radio" name="prive" value="1" default=$checked label="Recherche privée" help="Visible seulement par moi-même"}
				{input type="radio" name="prive" value="0" default=$checked label="Recherche publique" help="Visible et exécutable par tous les membres ayant accès à la gestion %s"|args:$target}
				<dt>Type</dt>
				<dd>
					{if $recherche.type == Recherche::TYPE_JSON}
						Avancée
					{elseif $recherche.type == Recherche::TYPE_SQL_UNPROTECTED}
						SQL non protégée
					{else}
						SQL
					{/if}</dd>
				<dt>Cible</dt>
				<dd>{$recherche.cible}</dd>
			</dl>
		</fieldset>

		<p class="submit">
			{csrf_field key="edit_recherche_%s"|args:$recherche.id}
			{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
		</p>
	</form>
{elseif $mode == 'delete'}

	{include file="common/delete_form.tpl"
		legend="Supprimer cette recherche enregistrée ?"
		warning="Êtes-vous sûr de vouloir supprimer la recherche enregistrée « %s » ?"|args:$recherche.intitule
		csrf_key="del_recherche_%s"|args:$recherche.id
	}

{elseif count($liste) == 0}
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
			{foreach from=$liste item="recherche"}
			<tr>
				<th><a href="{$search_url}?id={$recherche.id}">{$recherche.intitule}</a></th>
				<td>{if $recherche.type == Recherche::TYPE_JSON}Avancée{else}SQL{/if}</td>
				<td>{if !$recherche.id_membre}Publique{else}Privée{/if}</td>
				<td class="actions">
					{linkbutton href="%s?id=%d"|args:$search_url,$recherche.id shape="search" label="Rechercher"}
					{if $recherche.id_membre || $session->canAccess($target, Membres::DROIT_ADMIN)}
						{linkbutton href="?duplicate=%d"|args:$recherche.id shape="export" label="Dupliquer"}
						{linkbutton href="?edit=%d"|args:$recherche.id shape="edit" label="Modifier"}
						{linkbutton href="?delete=%d"|args:$recherche.id shape="delete" label="Supprimer"}
					{/if}
				</td>
			</tr>
			{/foreach}
		</tbody>
	</table>
{/if}

{include file="admin/_foot.tpl"}