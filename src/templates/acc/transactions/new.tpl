{include file="admin/_head.tpl" title="Saisie d'une écriture" current="acc/new"}

{include file="acc/_year_select.tpl"}

<form method="post" action="{$self_url}" data-focus="1">
	{form_errors}

	{if $ok}
		<p class="block confirm">
			L'écriture numéro {link href="details.php?id=%d"|args:$ok class="num" label=$ok} a été ajoutée.
			{linkbutton shape="menu" href="details.php?id=%d"|args:$ok label="Voir l'écriture"}
			{linkbutton href="?copy=%d"|args:$ok shape="plus" label="Dupliquer"}
		</p>
	{/if}

	<fieldset>
		<legend>Type d'écriture</legend>
		<dl>
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
		</dl>
	</fieldset>

	<fieldset>
		<legend>Informations</legend>
		<dl>
			{input type="date" name="date" label="Date" required=1 source=$transaction}
			{input type="text" name="label" label="Libellé" required=1 source=$transaction}
			{input type="text" name="reference" label="Numéro de pièce comptable" help="Numéro de facture, de note de frais, etc."}
		</dl>
		<dl data-types="all-but-advanced">
			{input type="money" name="amount" label="Montant" required=1 default=$amount}
		</dl>
	</fieldset>

	{foreach from=$types_details item="type"}
		<fieldset data-types="t{$type.id}">
			<legend>{$type.label}</legend>
			{if $type.id == $transaction::TYPE_ADVANCED}
				{* Saisie avancée *}
				{include file="acc/transactions/_lines_form.tpl" chart_id=$current_year.id_chart}
			{else}
				<dl>
				{foreach from=$type.accounts key="key" item="account"}
					<?php $selected = $types_accounts[$key] ?? null; ?>
					{input type="list" target="acc/charts/accounts/selector.php?targets=%s&chart=%d"|args:$account.targets_string,$chart_id name="account_%d_%d"|args:$type.id,$key label=$account.label required=1 default=$selected}
				{/foreach}
				</dl>
			{/if}
		</fieldset>
	{/foreach}

	<fieldset>
		<legend>Détails facultatifs</legend>
		<dl data-types="t{$transaction::TYPE_REVENUE} t{$transaction::TYPE_EXPENSE} t{$transaction::TYPE_TRANSFER}">
			{input type="text" name="payment_reference" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc." source=$transaction}
		</dl>
		<dl>
			{input type="list" multiple=true name="users" label="Membres associés" target="membres/selector.php"}
			{input type="textarea" name="notes" label="Remarques" rows=4 cols=30}
		</dl>
		<dl data-types="all-but-advanced">
			{if count($analytical_accounts) > 1}
				{input type="select" name="id_analytical" label="Projet (compte analytique)" options=$analytical_accounts default=$id_analytical}
			{/if}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_transaction_new"}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

<script type="text/javascript" defer="defer" async="async">
let is_new = {if null !== $transaction->type}false{else}true{/if};
{literal}
g.script('scripts/accounting.js', () => { initTransactionForm(is_new && !$('.block').length); });
</script>
{/literal}

{include file="admin/_foot.tpl"}