{include file="admin/_head.tpl" title=$chart.label current="acc/years"}

{include file="acc/charts/accounts/_nav.tpl" current="all"}

<p class="help">
	Les comptes marqués comme «&nbsp;<em>Ajouté</em>&nbsp;» ont été ajoutés au plan comptable officiel par vous-même.
</p>

<form method="post" action="{$self_url}">

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="account"}
		<tr class="account account-level-{$account.level}">
			<td>{$account.code}</td>
			<th>{$account.label}</th>
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
						{linkbutton shape="delete" label="Supprimer" href="!acc/charts/accounts/delete.php?id=%d"|args:$account.id}
					{/if}
					{linkbutton shape="edit" label="Modifier" href="!acc/charts/accounts/edit.php?id=%d"|args:$account.id}
				{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>

<script type="text/javascript">
{literal}
$('button[name]').forEach((b) => {
	b.onclick = () => {
		b.value = parseInt(b.value) ? 0 : 1;
		b.setAttribute('data-icon', b.value == 1 ? '☑' : '☐');
		fetch(document.forms[0].action, {
			'method': 'POST',
			'headers': {"Content-Type": "application/x-www-form-urlencoded"},
			'body': b.name + '=' + b.value
		});
		return false;
	};
});
{/literal}
</script>

</form>

{include file="admin/_foot.tpl"}