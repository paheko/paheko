{include file="_head.tpl" title="Détails du Paiement n°%d"|args:$payment->id current="payments"}

<dl class="describe">
	<dt>Libellé</dt>
	<dd>{$payment->label}</dd>
	<dt>Statut</dt>
	<dd>{$payment->status}</dd>
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
	<dt>Auteur/trice</dt>
	<dd>{if $author}{link href="!users/details.php?id=%d"|args:$author->id label=$author->nom}{else}{$payment->author_name}{/if}</dd>
	<dt>Écriture comptable</dt>
	<dd>{if $payment->id_transaction}<mark>{link href="!acc/transactions/details.php?id=%d"|args:$payment->id_transaction label=$payment->id_transaction}</mark>{else}-{/if}</dd>
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