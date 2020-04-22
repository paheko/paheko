{include file="admin/_head.tpl" title=$plan.label current="acc/plans"}

<ul class="actions">
	<li><a href="{$admin_url}acc/plans/">Gérer les plans</a></li>
	<li><a href="{$admin_url}acc/plans/import.php">Import / export</a></li>
	<li class="current"><a href="{$admin_url}acc/plans/accounts/?id={$plan.id}">Modifier le plan</a></li>
</ul>

{if count($accounts)}
	<table class="list accounts">
		<thead>
			<td>Code</td>
			<th>Libellé</th>
			<td>Position</td>
			<td></td>
			<td></td>
			<td></td>
		</thead>
		<tbody>
			{foreach from=$accounts item="item"}
				<tr class="account-level-<?=strlen($item->code)?>">
					<td>{$item.code}</td>
					<th>{$item.label}</th>
					<td>{$item.position|account_position}</td>
					<td>{$item.type|account_type}</td>
					<td>{if $item.bookmark}Favori{/if}</td>
					<td class="actions">
						{if !$item.user}
							<a class="icn" href="{$admin_url}acc/plans/delete.php?id={$item.id}" title="Supprimer">✘</a>
						{/if}
						<a class="icn" href="{$admin_url}acc/plans/edit.php?id={$item.id}" title="Modifier">✎</a>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{/if}

<form method="post" action="{$self_url_no_qs}">
	<fieldset>
		<legend>Ajouter un compte au plan comptable</legend>
		<dl>
			{input type="text" name="label" label="Libellé" required=1}
		</dl>
		<p class="submit">
			<input type="submit" value="Créer &rarr;" />
		</p>
	</fieldset>
</form>

{include file="admin/_foot.tpl"}