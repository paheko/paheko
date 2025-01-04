{include file="_head.tpl" title=$chart.label current="acc/years"}

{include file="acc/charts/accounts/_nav.tpl" current="favorites"}

<form method="post" action="all.php?id={$chart.id}">

	<p class="actions quick-search">
		<input type="text" placeholder="Recherche rapide…" title="Filtrer la liste" />{button shape="delete" type="reset" title="Effacer la recherche"}
		{* We can't use input type="search" because Firefox sucks *}
	</p>


	<p class="help">
		Cette liste regroupe les comptes de banque, caisse, attente, tiers, dépense, recette ou bénévolat qui sont soit marqués comme favori, soit ajoutés manuellement, soit déjà utilisés dans un exercice.
	</p>

<table class="list">
{foreach from=$accounts_grouped item="group"}
	<tbody>
		<tr class="no-border">
			<td><span class="ruler-left"></span></td>
			<td colspan="3"><h2 class="ruler-left">{$group.label}</h2></td>
			<td class="actions">
				{if !$chart.archived && $group.type}
					{linkbutton label="Ajouter un compte" shape="plus" href="!acc/charts/accounts/new.php?id=%d&type=%d&%s"|args:$chart.id:$group.type:$types_arg target=$dialog_target}
				{/if}
			</td>
		</tr>

	{foreach from=$group.accounts item="account"}
		<tr class="account">
			<td class="num">{$account.code}</td>
			<th>{$account.label}</th>
			<td class="desc">{$account.description}</td>
			<td>
				<?php
				$shape = $account->bookmark ? 'check' : 'uncheck';
				$title = $account->bookmark ? 'Ôter des favoris' : 'Marquer comme favori';
				?>
				{button shape=$shape name="bookmark[%d]"|args:$account.id value=$account.bookmark label="Favori" title=$title type="submit"}
			</td>
			<td class="actions">
				{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN) && !$chart.archived}
					{if (!$chart->code || $account->user) && $account->canDelete()}
						{linkbutton shape="delete" label="Supprimer" href="!acc/charts/accounts/delete.php?id=%d&%s"|args:$account.id:$types_arg  target=$dialog_target}
					{/if}
					{linkbutton shape="edit" label="Modifier" href="!acc/charts/accounts/edit.php?id=%d&%s"|args:$account.id:$types_arg  target=$dialog_target}
				{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
{/foreach}
</table>

<script type="text/javascript" src="{$admin_url}static/scripts/accounts_list.js"></script>

</form>

{include file="_foot.tpl"}