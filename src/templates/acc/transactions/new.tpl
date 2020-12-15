{include file="admin/_head.tpl" title="Saisie d'une écriture" current="acc/new"}

{include file="acc/_year_select.tpl"}

<form method="post" action="{$self_url}" enctype="multipart/form-data" data-focus="1">
	{form_errors}

	{if $ok}
		<p class="block confirm">
			L'écriture numéro <a href="details.php?id={$ok}">{$ok}</a> a été ajoutée.
			(<a href="details.php?id={$ok}">Voir l'écriture</a>)
		</p>
	{/if}

	{if $payoff_for}
		<input type="hidden" name="type" value="{$transaction.type}" />
		<input type="hidden" name="payoff_for" value="{$payoff_for.id}" />
		<input type="hidden" name="{$payoff_for.form_account_name}[{$payoff_for.id_account}]" value="-" />
		<fieldset>
			<legend>{if $payoff_for.type == $transaction::TYPE_DEBT}Règlement de dette{else}Règlement de créance{/if}</legend>
			<dl>
				<dt>Écriture d'origine</dt>
				<dd><a class="num" href="{$admin_url}acc/transactions/details.php?id={$payoff_for.id}">#{$payoff_for.id}</a></dd>
				{input type="list" target="acc/charts/accounts/selector.php?targets=%s&chart=%d"|args:$payoff_targets,$chart_id name=$payoff_for.form_target_name label="Compte de règlement" required=1}
			</dl>
		</fieldset>
	{else}
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
								<p>{$type.help}</p>
							{/if}
						</div>
					</label>
				</dd>
			{/foreach}
			</dl>
		</fieldset>
	{/if}

	<fieldset>
		<legend>Informations</legend>
		<dl>
			{input type="date" name="date" default=$date label="Date" required=1 source=$transaction}
			{input type="text" name="label" label="Libellé" required=1 source=$transaction}
			{input type="text" name="reference" label="Numéro de pièce comptable" help="Numéro de facture, de note de frais, etc."}
		</dl>
		<dl data-types="all-but-advanced">
			{input type="money" name="amount" label="Montant" required=1 default=$amount}
		</dl>
	</fieldset>

	{if !$payoff_for}

		{foreach from=$types_details item="type"}
			<fieldset data-types="t{$type.id}">
				<legend>{$type.label}</legend>
				{if $type.id == $transaction::TYPE_ADVANCED}
					{* Saisie avancée *}
					{include file="acc/transactions/_lines_form.tpl" chart_id=$current_year.id_chart}
				{else}
					<dl>
					{foreach from=$type.accounts key="key" item="account"}
						{input type="list" target="acc/charts/accounts/selector.php?targets=%s&chart=%d"|args:$account.targets_string,$chart_id name="account_%d_%d"|args:$type.id,$key label=$account.label required=1}
					{/foreach}
					</dl>
				{/if}
			</fieldset>
		{/foreach}
	{/if}

	<fieldset>
		<legend>Détails facultatifs</legend>
		<dl data-types="t{$transaction::TYPE_REVENUE} t{$transaction::TYPE_EXPENSE} t{$transaction::TYPE_TRANSFER}">
			{input type="text" name="payment_reference" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc." source=$transaction}
		</dl>
		<dl>
			{input type="list" multiple=true name="users" label="Membres associés" target="membres/selector.php"}
			{input type="textarea" name="notes" label="Remarques" rows=4 cols=30}

			{input type="file" name="file" label="Fichier joint"}
		</dl>
		<dl data-types="all-but-advanced">
			{if count($analytical_accounts) > 1}
				{input type="select" name="id_analytical" label="Projet (compte analytique)" options=$analytical_accounts}
			{/if}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_transaction_new"}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

<script type="text/javascript" defer="defer" async="async">
let is_new = {if $payoff_for || null !== $transaction->type}false{else}true{/if};
{literal}
g.script('scripts/accounting.js', () => { initTransactionForm(is_new && !$('.block').length); });
</script>
{/literal}

{include file="admin/_foot.tpl"}