{if $pending_count}
	<p class="alert block">
		{
			{Il y a une dette ou créance à régler dans un autre exercice.}
			{Il y a %n dettes ou créances à régler dans d'autres exercices.}
			n=$pending_count
		}<br />
		{linkbutton href="!acc/transactions/pending.php" label="Voir les dettes et créances en attente" shape="menu"}
	</p>
{/if}

{* DISABLED for now
{if !empty($pending_deposit_accounts)}
	<div class="alert block">
		<p>Des écritures d'autres exercices sont en attente de dépôt&nbsp;:</p>
		<ul>
			{foreach from=$pending_deposit_accounts item="account"}
			<li>{link label="%s — %s"|args:$account.code:$account.label href="!acc/accounts/deposit.php?id=%d&only=0"|args:$account.id}</li>
			{/foreach}
		</ul>
	</div>
{/if}
*}