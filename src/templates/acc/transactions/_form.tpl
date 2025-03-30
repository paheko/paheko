<?php

$is_new = empty($_POST) && !isset($transaction->type) && !$transaction->exists() && !$transaction->label;
$is_quick = count(array_intersect_key($_GET, array_flip(['a', 'l', 'd', 't', 'account']))) > 0;

?>
<form method="post" action="{$self_url}" data-focus="{if $is_new || $is_quick}1{else}#f_date{/if}">
	{form_errors}

	<fieldset>
		<legend>Type d'écriture</legend>
		<dl>
		{if isset($payoff)}
			{input type="radio-btn" name="type" value=99 source=$transaction label=$payoff.type_label}
			{input type="radio-btn" name="type" value=0 source=$transaction label="Avancé"}
		{else}
			{foreach from=$types_details item="type"}
				<dd class="radio-btn">
					{input type="radio" name="type" value=$type.id source=$transaction label=null}
					<label for="f_type_{$type.id}">
						<div>
							<h3>{$type.label}</h3>
							{if !empty($type.help)}
								<p class="help">{$type.help}</p>
							{/if}
						</div>
					</label>
				</dd>
			{/foreach}
		{/if}
		</dl>
	</fieldset>

	{if isset($payoff)}
		<fieldset>
			<legend>{if $payoff.type == $transaction::TYPE_DEBT}Règlement de dette{else}Règlement de créance{/if}</legend>
			<dl>
				<dt>Écritures d'origine</dt>
				{foreach from=$payoff.transactions item="t"}
					<dd>{link class="num" href="!acc/transactions/details.php?id=%d"|args:$t.id label="#%d"|args:$t.id} — {$t.label} — {$t->sum()|money_currency|raw}</dd>
				{/foreach}
				{input type="checkbox" name="mark_paid" value="1" default="1" label="Marquer ces écritures comme réglées"}
			</dl>
		</fieldset>
	{/if}

	<fieldset{if $is_new} class="hidden"{/if}>
		<legend>Informations</legend>
		<dl>
			{input type="date" name="date" label="Date" required=1 source=$transaction}
			{input type="text" name="label" label="Libellé" required=1 source=$transaction}
			{input type="text" name="reference" label="Numéro de pièce comptable" help="Numéro de facture, de reçu, de note de frais, etc." source=$transaction}
		</dl>
		<dl data-types="all-but-advanced">
			{if !isset($payoff) || !$payoff.multiple}
				{input type="money" name="amount" label="Montant" required=1 default=$amount}
			{else}
				{input type="money" name="amount" label="Montant" required=1 default=$amount disabled=true help="Le montant ne peut être modifié pour le règlement de plusieurs écritures."}
			{/if}
		</dl>
	</fieldset>

	{if !empty($has_reconciled_lines)}
	<p class="alert block">
		Attention, cette écriture contient des lignes qui ont été rapprochées. Modifier son montant ou le compte bancaire entraînera la perte du rapprochement.
	</p>
	{/if}

	{if isset($payoff)}
		<fieldset data-types="t99"{if $is_new} class="hidden"{/if}>
			<legend>{$payoff.type_label}</legend>
			<dl>
				{input type="list" target="!acc/charts/accounts/selector.php?types=%s&id_chart=%d"|args:$payoff.selector_types:$chart.id name="payoff_account" label="Compte de règlement" required=1}
			</dl>
		</fieldset>
	{/if}

	{foreach from=$types_details item="type"}
		<fieldset data-types="t{$type.id}"{if $is_new} class="hidden"{/if}>
			<legend>{$type.label}</legend>
			{if $type.id == $transaction::TYPE_ADVANCED}
				{* Saisie avancée *}
				{include file="acc/transactions/_lines_form.tpl" chart_id=$chart.id}
			{else}
				<dl>
				{foreach from=$type.accounts key="key" item="account"}
					{input type="list" target="!acc/charts/accounts/selector.php?types=%s&id_chart=%d"|args:$account.types_string:$chart.id name=$account.selector_name label=$account.label required=1 default=$account.selector_value}
				{/foreach}
				</dl>
			{/if}
		</fieldset>
	{/foreach}

	<fieldset{if $is_new} class="hidden"{/if}>
		<legend>Détails facultatifs</legend>
		<dl data-types="t{$transaction::TYPE_REVENUE} t{$transaction::TYPE_EXPENSE} t{$transaction::TYPE_TRANSFER} t99">
			{input type="text" name="payment_reference" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc." default=$transaction->getPaymentReference()}
		</dl>
		<dl>
			{input type="list" multiple=true name="users" label="Membres associés" target="!users/selector.php" default=$linked_users}
			{input type="textarea" name="notes" label="Remarques" rows=4 cols=30 source=$transaction}
		</dl>
		{if !isset($payoff)}
			<dl data-types="t{$transaction::TYPE_ADVANCED} t{$transaction::TYPE_DEBT} t{$transaction::TYPE_CREDIT}">
				{input type="list" name="linked" label="Écritures liées" default=$linked_transactions target="!acc/transactions/selector.php" multiple=true}
			</dl>
		{/if}
		<dl data-types="all-but-advanced">
			{if !empty($projects)}
				{input type="select" name="id_project" label="Projet (analytique)" options=$projects default=$id_project default_empty="— Aucun —" required=$config.analytical_mandatory}
			{/if}
		</dl>
	</fieldset>

	<p class="submit{if $is_new} hidden{/if}">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE)}
	<p class="submit help{if $is_new} hidden{/if}">
		Vous pourrez ajouter des fichiers à cette écriture une fois qu'elle aura été enregistrée.
	</p>
{/if}

</form>

<script type="text/javascript" async="async">
let is_new = {$is_new|escape:'json'};
{literal}
window.addEventListener('load', () => {
	g.script('scripts/accounting.js', () => { initTransactionForm(is_new && !$('.block').length); });
});
</script>
{/literal}