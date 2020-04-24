{include file="admin/_head.tpl" title=$chart.label current="acc/charts"}

<ul class="actions">
	<li><a href="{$admin_url}acc/charts/">Gérer les plans</a></li>
	<li class="current"><a href="{$admin_url}acc/accounts/?id={$chart.id}">Modifier le plan</a></li>
	<li><a href="{$admin_url}acc/charts/export.php?id={$chart.id}">Exporter ce plan en CSV</a></li>
	<li><a href="{$admin_url}acc/charts/import.php?id={$chart.id}">Importer</a></li>
	<li><a href="{$admin_url}acc/charts/delete.php?id={$chart.id}">Supprimer</a></li>
	<li><a href="{$admin_url}acc/charts/reset.php?id={$chart.id}">Remettre à zéro</a></li>
</ul>

{if count($accounts)}
	<table class="list accounts">
		<thead>
			<td>Code</td>
			<th>Libellé</th>
			<td>Position</td>
			<td></td>
			<td></td>
		</thead>
		<tbody>
			{foreach from=$accounts item="item"}
				<tr class="account-level-<?=strlen($item->code)?>">
					<td>{$item.code}</td>
					<th>{$item.label}</th>
					<td>{$item.position|account_position}</td>
					<td>
						{if $item.type == $item::TYPE_BOOKMARK}{icon shape="star"}{/if}
						{$item.type|account_type}
					</td>
					<td class="actions">
						{if $item.user}
							{icon shape="delete" label="Supprimer" href="acc/charts/accounts/delete.php?id=%d"|args:$item.id}
						{/if}
						{icon shape="edit" label="Modifier" href="acc/charts/accounts/edit.php?id=%d"|args:$item.id}
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
			{input type="text" name="code" label="Code" required=1 pattern="\d+" maxlength=6 help="Utilisé pour ordonner la liste des comptes. Seuls les chiffres sont acceptés."}
			{input type="text" name="label" label="Libellé" required=1}
		</dl>
		<p class="submit">
			<input type="submit" value="Créer &rarr;" />
		</p>
	</fieldset>
</form>

{include file="admin/_foot.tpl"}