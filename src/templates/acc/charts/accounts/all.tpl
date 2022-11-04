{include file="admin/_head.tpl" title=$chart.label current="acc/years"}

{include file="acc/charts/accounts/_nav.tpl" current="all"}

<p class="help">
	Les comptes marqués comme «&nbsp;<em>Ajouté</em>&nbsp;» ont été ajoutés au plan comptable officiel par vous-même.
</p>

<form method="post" action="{$self_url}">

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="account"}
		<tr class="account account-level-{$account.level}">
			<td class="num">{$account.code}</td>
			<th>{$account.label}
				{if $account.description}
				<br /><p class="help block">{$account.description}</p>
				{/if}
			</th>
			<td>
				<?php
				$shape = $account->bookmark ? 'check' : 'uncheck';
				$title = $account->bookmark ? 'Ôter des favoris' : 'Marquer comme favori';
				?>
				{button shape=$shape name="bookmark[%d]"|args:$account.id value=$account.bookmark label="Favori" title=$title type="submit"}
			</td>
			<td>
				{if $account.user}<em>Ajouté</em>{/if}
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

<script type="text/javascript" src="{$admin_url}static/scripts/accounts_bookmark.js"></script>

</form>

{include file="admin/_foot.tpl"}