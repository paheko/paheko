{include file="admin/_head.tpl" title="Comptes usuels" current="acc/years"}

{include file="acc/charts/accounts/_nav.tpl" current="favorites"}

<p class="help">
	Cette liste regroupe les comptes de banque, caisse, attente, tiers, dépense, recette ou bénévolat qui sont soit marqués comme favori, soit ajoutés manuellement, soit déjà utilisés dans un exercice.
</p>

<form method="post" action="all.php?id={$chart.id}">

<table class="list">
{foreach from=$accounts_grouped item="group"}
	<tbody>
		<tr>
			<td colspan="4"><h2 class="ruler">{$group.label}</h2></td>
			<td class="actions">
				{if !$chart.archived}
					{linkbutton label="Ajouter un compte" shape="plus" href="!acc/charts/accounts/new.php?id=%d&type=%d"|args:$chart.id,$group.type  target="_dialog=manage"}
				{/if}
			</td>
		</tr>

	{foreach from=$group.accounts item="account"}
		<tr class="account">
			<td>{$account.code}</td>
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
						{linkbutton shape="delete" label="Supprimer" href="!acc/charts/accounts/delete.php?id=%d"|args:$account.id  target="_dialog=manage"}
					{/if}
					{linkbutton shape="edit" label="Modifier" href="!acc/charts/accounts/edit.php?id=%d"|args:$account.id  target="_dialog=manage"}
				{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
{/foreach}
</table>

<script type="text/javascript" src="{$admin_url}static/scripts/accounts_bookmark.js"></script>
</form>

{include file="admin/_foot.tpl"}