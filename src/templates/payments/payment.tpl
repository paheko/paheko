{include file="_head.tpl" title="Détails du Paiement n°%d"|args:$payment->id current="payments"}
{include file="payments/_menu.tpl"}

<dl class="describe">
	<dt>Libellé</dt>
	<dd>{$payment->label}</dd>
	<dt>Statut</dt>
	<dd>{$statuses[$payment->status]}</dd>
	<dt>Montant</dt>
	<dd>{$payment->amount|money_currency|raw}</dd>
	<dt>Référence</dt>
	<dd>{$payment->reference}</dd>
	<dt>Prestataire</dt>
	<dd>{if $provider}{$provider->label}{else}{$payment->provider}{/if}</dd>
	<dt>Méthode</dt>
	<dd>{$methods[$payment->method]}</dd>
	<dt>Type</dt>
	<dd>{$types[$payment->type]}</dd>
	<dt>Payeur/euse</dt>
	<dd>{if $payer}{link href="!users/details.php?id=%d"|args:$payer->id label=$payer->nom}{else}{$payment->payer_name}{/if}</dd>
	<dt>Initiateur/trice</dt>
	<dd>
		{if $author}
			{link href="!users/details.php?id=%d"|args:$author->id label=$author->nom}
		{else}
			Prestataire {$provider->label}
		{/if}
	</dd>
	<dt>Membres concerné·e·s</dt>
	<dd class="num">
		<ul class="flat">
		{foreach from=$users item='user'}
			<li>{$user->nom} {link href="!users/details.php?id=%d"|args:$user->id label=$user->numero}{if isset($users_notes[$user->id])} ({$users_notes[$user->id]}){/if}</li>
		{/foreach}
		</ul>
	</dd>
	<dt>Écritures comptables</dt>
	<dd class="num">
		{if $transactions}
			{foreach from=$transactions item='transaction'}
				<mark>{link href="!acc/transactions/details.php?id=%d"|args:$transaction->id label='#'|cat:$transaction->id}</mark>
			{/foreach}
		{/if}
	</dd>

	<dt>Historique</dt>
	<dd>{$payment->history|escape|nl2br}</dd>
</dl>


{if $TECH_DETAILS}
	<dl style="background-color: black; color: limegreen; padding-top: 0.8em;" class="describe">
		<dt style="color: limegreen;">extra_data</dt>
		<dd><pre>{$payment->extra_data|dump}</pre></dd>
	</dl>
{/if}

{include file="_foot.tpl"}