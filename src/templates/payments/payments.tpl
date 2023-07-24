{if isset($provider)}
	{assign var='title' value="Paiements - %s"|args:$provider->label}
{else}
	{assign var='title' value=$payments->title}
{/if}
{include file="_head.tpl" title=$title current="payments"}

{include file="payments/_menu.tpl"}

{if isset($_GET.ok)}
	<p class="confirm block">Paiement créé avec succès.</p>
{/if}

<h2 class="ruler">Liste des paiements{if isset($provider)} pour {$provider->label}{/if}</h2>

{include file="common/dynamic_list_head.tpl" list=$payments}

	<tbody>

	{foreach from=$payments->iterate() item="row"}
		<tr>
			<td class="num">{link href="!payments/payments.php?id=%s"|args:$row.id label=$row.reference}</td>
			<td class="num">
				{if $row.users}
					{foreach from=$row.users item="id_user"}
						{link href="!users/details.php?id=%d"|args:$id_user label="#%d"|args:$id_user}
					{/foreach}
				{/if}
			</td>
			<td>{if $row.id_payer}{link href="!users/details.php?id=%d"|args:$row.id_payer label=$row.payer_name}{else}{$row.payer_name}{/if}</td>
			{* Fallback to "provider" field when provider has been uninstalled *}
			<td>{if $row.provider_label}{$row.provider_label}{else}{$row.provider}{/if}</td>
			<td>{$row.type}</td>
			<td>{$row.status}</td>
			<td>{$row.label}</td>
			<td class="money">{$row.amount|money_currency|raw}</td>
			<td>{$row.date|date}</td>
			<td>{$row.method}</td>
			<td class="num">
				{if $row.transactions}
					{foreach from=$row.transactions item="id_transaction"}
						{link href="!acc/transactions/details.php?id=%d"|args:$id_transaction label="#%d"|args:$id_transaction}
					{/foreach}
				{/if}
			</td>
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