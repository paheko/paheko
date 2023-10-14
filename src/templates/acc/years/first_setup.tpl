{include file="_head.tpl" title="Démarrer la comptabilité" current="acc"}

{form_errors}

<div class="help block">
	<h3>Bienvenue dans la comptabilité&nbsp;!</h3>
	<p>Les informations ci-dessous sont nécessaire pour démarrer la comptabilité.</p>
	<p>{linkbutton shape="help" href=$help_pattern_url|args:"premier-exercice" target="_dialog" label="Démarrer le premier exercice comptable"}</p>
</div>

<form method="post" action="{$self_url}" data-focus="1">

{if $step == 0}
	<fieldset>
		<legend>1. Plan comptable</legend>
		<p class="help">
			Le plan comptable contient la liste des comptes selon les postes (dépenses, recettes, etc.).
		</p>
		<dl>
			<dt>Pays</dt>
			<dd>{$config.country|get_country_name} {linkbutton href="!config/" shape="settings" label="Modifier le pays dans la configuration"}</dd>
		</dl>
		{if $default_chart && $year.id_chart == $default_chart.id}
		<dl class="chart-default">
			<dt><label for="f_change_chart">Plan comptable recommandé</label></dt>
			<dd>{$default_chart.label} {button id="f_change_chart" shape="edit" label="Choisir un autre plan comptable" onclick="g.toggle('.chart-default', false); g.toggle('.charts', true);"}</dd>
			<dd class="help">Le choix du plan comptable ne peut être modifié une fois que l'exercice sera ouvert.<br />Mais il sera possible d'y ajouter de nouveaux comptes si nécessaire.</dd>
		</dl>
		<dl class="charts hidden">
		{else}
		<dl>
		{/if}
			{input type="select" options=$charts_list label="Plan comptable" required=true name="chart" default=$default_chart_code}
			<dd>{linkbutton shape="edit" href="!acc/charts/" label="Gérer les plans comptables"}</dd>
		</dl>
	</fieldset>

	<fieldset>
		<legend>2. Premier exercice</legend>
		<p class="help">
			La comptabilité utilise des exercices. Un exercice, c'est une période comptable, généralement d'une année (12 mois), souvent une année civile, du 1<sup>er</sup> janvier au 31 décembre, mais d'autres choix sont possibles.
			{linkbutton shape="help" href=$help_pattern_url|args:"exercice-comptable" target="_dialog" label="Qu'est-ce qu'un exercice comptable ?"}
		</p>
		<dl>
			{input type="date" label="Date de début de l'exercice" name="start_date" required=true source=$year}
			{input type="date" label="Date de fin de l'exercice" name="end_date" required=true source=$year}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="step" value="1" label="Étape suivante" shape="right" class="main"}
	</p>

{else}
	<fieldset>
		<legend>3. Comptes bancaires</legend>
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
		<legend>4. Résultat précédent</legend>
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
		{input type="hidden" name="start_date" default=$year.start_date}
		{input type="hidden" name="end_date" default=$year.end_date}
		{input type="hidden" name="id_chart" default=$year.id_chart}
		{button type="submit" name="step" value="0" label="Retour" shape="left" }
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>
{/if}

</form>

<script type="text/javascript" src="{$admin_url}static/scripts/accounting_setup.js"></script>

{include file="_foot.tpl"}