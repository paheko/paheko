{include file="_head.tpl" title="Paiements" current="payments"}

{include file="payments/_menu.tpl"}

<h2 class="ruler">Liste des paiements</h2>

{include file="common/dynamic_list_head.tpl" list=$payments}

	<tbody>

	{foreach from=$payments->iterate() item="row"}
		<tr>
			<td class="num">{link href="!payments/payments.php?id=%s"|args:$row.id label=$row.reference}</td>
			<td class="num">{link href="!acc/transactions/details.php?id=%d"|args:$row.id_transaction label=$row.id_transaction}</td>
			<td>{if $row.id_author}{link href="!users/details.php?id=%d"|args:$row.id_author label=$row.author_name}{else}{$row.author_name}{/if}</td>
			{* Fallback to "provider" field when provider has been uninstalled *}
			<td>{if $row.provider_label}{$row.provider_label}{else}{$row.provider}{/if}</td>
			<td>{$row.type}</td>
			<td>{$row.status}</td>
			<td>{$row.label}</td>
			<td class="money">{$row.amount|money_currency|raw}</td>
			<td>{$row.date|date}</td>
			<td>{$row.method}</td>
			<td class="actions">{linkbutton href="%spayments/payments.php?id=%s"|args:$admin_url:$row.id shape="help" label="Détails"}</td>
		</tr>
	{/foreach}

	</tbody>
</table>

{$payments->getHTMLPagination()|raw}

{if $_GET.ok}
	<p class="confirm block">Paiement enregistré avec succès</p>
{/if}

{include file="_foot.tpl"}