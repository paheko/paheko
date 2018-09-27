{include file="admin/_head.tpl" title="Recherches enregistrées" current="membres"}

{include file="admin/membres/_nav.tpl" current="recherches"}

{if count($liste) == 0}
	<p class="alert">Aucune recherche enregistrée. <a href="{$admin_url}membres/recherche.php">Faire une nouvelle recherche</a></p>
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
				<th>{$recherche.intitule}</th>
				<td>{if $recherche.type == Recherche::TYPE_JSON}Avancée{else}SQL{/if}</td>
				<td>{if !$recherche.id_membre}Publique{else}Personnelle{/if}</td>
				<td class="actions">
					<a href="{$admin_url}membres/recherches.php?id={$recherche.id}" class="icn" title="Modifier">✎</a>
				</td>
			</tr>
			{/foreach}
		</tbody>
	</table>
{/if}

{include file="admin/_foot.tpl"}