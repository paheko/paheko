{include file="_head.tpl" title=$chart.label current="acc/years"}

{include file="acc/charts/accounts/_nav.tpl" current="all"}

<form method="post" action="{$self_url}" data-focus="1">

	<p class="actions quick-search">
		<input type="text" placeholder="Recherche rapide…" title="Filtrer la liste" />{button shape="delete" type="reset" title="Effacer la recherche"}
		{* We can't use input type="search" because Firefox sucks *}
	</p>

	<p class="help">
		Les comptes marqués comme «&nbsp;<em>Ajouté</em>&nbsp;» ont été ajoutés au plan comptable officiel par vous-même.
	</p>

{if !$list->count() && $types_names}
	<p class="block alert">
		Il n'existe aucun compte dans la catégorie «&nbsp;{$types_names}&nbsp;» dans le plan comptable.
	</p>
{else}
	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="account"}
			<tr class="account account-level-{$account.level}">
				<td class="num">{$account.code}</td>
				<th{if !$account.description} colspan=2{/if}>{$account.label}</th>
				{if $account.description}
				<td class="help">{$account.description|escape|nl2br}</td>
				{/if}
				<td>
					{$account.position_report}
				<td>
					{$account.position_name}
				</td>
				<td>
					{if $account.user}<em>Ajouté</em>{/if}
				</td>
				<td>
					<?php
					$shape = $account->bookmark ? 'check' : 'uncheck';
					$title = $account->bookmark ? 'Ôter des favoris' : 'Marquer comme favori';
					?>
					{button shape=$shape name="bookmark[%d]"|args:$account.id value=$account.bookmark label="Favori" title=$title type="submit"}
				</td>
				<td class="actions">
					{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN) && !$chart.archived}
						{if $account.user || !$chart.code}
							{linkbutton shape="delete" label="Supprimer" href="!acc/charts/accounts/delete.php?id=%d&%s"|args:$account.id,$types_arg target=$dialog_target}
						{/if}
						{linkbutton shape="edit" label="Modifier" href="!acc/charts/accounts/edit.php?id=%d%s"|args:$account.id,$types_arg target=$dialog_target}
					{/if}
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>

	<script type="text/javascript" src="{$admin_url}static/scripts/accounts_list.js"></script>
{/if}

</form>

{include file="_foot.tpl"}