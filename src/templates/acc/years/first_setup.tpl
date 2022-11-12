{include file="_head.tpl" title="Démarrer la comptabilité" current="acc"}

{form_errors}

<div class="help block">
	<h3>Bienvenue dans la comptabilité&nbsp;!</h3>
	<p>Pour commencer à saisir la comptabilité, merci d'indiquer les quelques informations ci-dessous.</p>
</div>

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>1. Premier exercice</legend>
		<p class="help">
			La comptabilité utilise des exercices. Un exercice c'est une période comptable, en général une année civile, du 1<sup>er</sup> janvier au 31 décembre.
		</p>
		<dl>
			{input type="date" label="Date de début de l'exercice" name="start_date" required=true source=$year}
			{input type="date" label="Date de fin de l'exercice" name="end_date" required=true source=$year}
		</dl>
	</fieldset>

	<fieldset>
		<legend>2. Comptes bancaires</legend>
		<p class="help">
			Créez ici vos comptes de banque (compte courant, livret, etc.) et de prestataires  de paiement (type Paypal, SumUp, HelloAsso, etc.).<br />
			Vous pouvez aussi indiquer le solde du compte à la date de début de l'exercice.
		</p>
		<table class="auto list">
			<thead>
				<tr>
					<th>Nom du compte</th>
					<td>Solde du compte</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				{foreach from=$new_accounts item="account"}
				<tr>
					<th>{input type="text" name="accounts[label][]" default=$account.label required=false}</th>
					<td>{input type="money" name="accounts[balance][]" default=$account.balance required=false}</td>
					<td class="actions">{button label="Enlever" title="Enlever la ligne" shape="minus" min="2" name="remove_line"}</td>
				</tr>
				{/foreach}
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2"></td>
					<td class="actions">{button label="Ajouter" title="Ajouter une ligne" shape="plus"}</td>
				</tr>
			</tfoot>
		</table>
	</fieldset>

	{if $appropriation_account}
	<fieldset>
		<legend>3. Résultat précédent</legend>
		<p class="help">
			Si vous aviez déjà réalisé une comptabilité auparavant, merci de reporter ci-dessous le résultat de l'exercice précédent.
		</p>
		<dl>
			{input type="money" label="Résultat de l'exercice précédent" name="previous_result" help="Si le résultat était en déficit, ajouter un signe moins (-) au début du nombre." name="result"}
		</dl>

	</fieldset>
	{/if}

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

<script type="text/javascript" src="{$admin_url}static/scripts/accounting_setup.js"></script>

{include file="_foot.tpl"}