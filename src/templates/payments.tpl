{include file="_head.tpl" title="Bonjour %s !"|args:$logged_user->name() current="payments"}

<h2>Providers</h2>

<ul>
{foreach from=$providers item='provider'}
	<li>{$provider->name}: {$provider->label}</li>
{/foreach}
</ul>

<h2>Payments</h2>

<ul>
{foreach from=$payments item='payment'}
	<li>{$payment->label} - {$payment.status}: {$payment->amount|escape|money_currency}</li>
{/foreach}
</ul>

{if $_GET.ok}
	<p class="confirm block">Paiement enregistré avec succès</p>
{/if}

<h2 class="ruler">Créer un paiement</h2>

<form method="POST" action="{$self_url}">
	<fieldset>
		<legend>Paiement</legend>
		<dl>{input type="text" name="label" label="Libellé" required=true}</dl>
		<dl>{input type="select" name="type" label="Type" options=Entities\Payments\Payment::TYPES default=Entities\Payments\Payment::UNIQUE_TYPE required=true}</dl>
		<dl>{input type="select" name="method" label="Méthode" options=Entities\Payments\Payment::METHODS required=true}</dl>
		<dl>{input type="select" name="provider" label="Prestataire" options=$provider_options default=Payments\Providers::MANUAL_PROVIDER required=true}</dl>
		<dl>{input type="list" name="author" label="Payeur/euse" target="!users/selector.php" can_delete="true" required=true}</dl>
		<dl>{input type="text" name="reference" label="Référence"}</dl>
		<dl>{input type="money" name="amount" label="Montant" required=true}</dl>
	</fieldset>
	{button type="submit" name="save" label="Créer" class="main"}
</form>

{include file="_foot.tpl"}