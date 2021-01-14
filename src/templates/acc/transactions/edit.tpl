{include file="admin/_head.tpl" title="Modification d'une écriture" current="acc/simple"}

<form method="post" action="{$self_url}" enctype="multipart/form-data" data-focus="#f_date">
	{form_errors}

	{if $has_reconciled_lines}
	<p class="alert block">
		Attention, cette écriture contient des lignes qui ont été rapprochées. La modification de cette écriture entraînera la perte du rapprochement.
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
							<p>{$type.help}</p>
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
			{input type="text" name="reference" label="Numéro de pièce comptable" help="Numéro de facture, de note de frais, etc." source=$transaction}
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
			{input type="text" name="payment_reference" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc." default=$first_line.reference}
		</dl>
		<dl>
			{input type="list" multiple=true name="users" label="Membres associés" target="membres/selector.php" default=$linked_users}
			{input type="textarea" name="notes" label="Remarques" rows=4 cols=30 source=$transaction}

			{input type="file" name="file" label="Ajouter un fichier joint"}
		</dl>
		<dl data-types="all-but-advanced">
			{if count($analytical_accounts) > 1}
				{input type="select" name="id_analytical" label="Projet (compte analytique)" options=$analytical_accounts default=$first_line.id_analytical}
			{/if}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_edit_%d"|args:$transaction.id}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{literal}
<script type="text/javascript" defer="defer" async="async">
g.script('scripts/accounting.js', () => { initTransactionForm(); });
</script>
{/literal}

{include file="admin/_foot.tpl"}