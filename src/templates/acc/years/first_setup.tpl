{include file="_head.tpl" title="Démarrer la comptabilité" current="acc"}

{form_errors}

<form method="{$method}" action="{$self_url_no_qs}" data-focus="1">

{if $step > 1}
	{$data|html_hidden_inputs|raw}
{/if}

{if $step === 1}
	<div class="alert block">
		<p>Avant de pouvoir commencer, merci de renseigner quelques informations pour pouvoir démarrer la comptabilité.</p>
	</div>

	<h2 class="step"><strong>1.</strong> Dates du premier exercice</h2>

	<div class="help block">
		<p>La comptabilité utilise des exercices.</p>
		<p>Un exercice, c'est une période comptable, généralement d'une année (12 mois), souvent une année civile, du 1<sup>er</sup> janvier au 31 décembre, mais d'autres choix sont possibles.</p>
		<p>{linkbutton shape="help" href=$help_pattern_url|args:"premier-exercice" target="_dialog" label="Qu'est-ce qu'un exercice comptable ?"}</p>
	</div>
	<fieldset>
		<legend>Configuration du premier exercice</legend>
		<dl>
			{input type="date" label="Date de début de l'exercice" name="start_date" required=true source=$year}
			{input type="date" label="Date de fin de l'exercice" name="end_date" required=true source=$year}
		</dl>
	</fieldset>

	<p class="submit">
		{button type="submit" name="step" value="2" label="Étape suivante" shape="right" class="main"}
	</p>

{elseif $step === 2}

	<h2 class="step"><strong>2.</strong> Choisir le plan comptable</h2>
	<div class="help block">
		<p>Le plan comptable contient la liste des comptes selon le type d'écriture comptable (dépenses, recettes, banque, caisse, etc.).</p>
		<p>Une fois l'exercice ouvert, le plan comptable sélectionné ne pourra plus être modifié. Il sera toujours possible d'ajouter des comptes dans le plan comptable sélectionné.</p>
	</div>
	<fieldset>
		<legend>Pays</legend>
		<dl>
			{foreach from=$countries key="code" item="name"}
				{input type="radio-btn" name="country" value=$code label=$name default=$config.country required=true}
			{/foreach}
		</dl>
	</fieldset>
	<fieldset class="chart hidden">
		<legend>Plan comptable</legend>
		{foreach from=$countries_charts key="country" item="charts"}
		<dl class="charts-{$country} hidden">
			{foreach from=$charts key="code" item="name"}
				{input type="radio-btn" name="chart" value=$code label=$name required=true}
			{/foreach}
			{if isset($countries_users_charts[$country])}
				<dt>Plan comptable personnalisé</dt>
				{foreach from=$countries_users_charts[$country] key="id" item="name"}
					{input type="radio-btn" name="chart" value=$id label=$name required=true}
				{/foreach}
			{/if}
		</dl>
		{/foreach}
		<p class="help">Besoin de créer votre propre plan comptable ? {link href="!acc/charts/" label="Cliquez ici pour importer ou créer un plan comptable personnalisé."}</p>
	</fieldset>

	<p class="submit hidden">
		{linkbutton label="Retour" shape="left" href=$back_url}
		{button type="submit" name="step" value="3" label="Étape suivante" shape="right" class="main"}
	</p>

{elseif $step === 3}

	<h2 class="step"><strong>3.</strong> Créer les comptes bancaires</h2>

	<div class="help block">
		<p>Créez ici vos comptes de banque (compte courant, livret, etc.) et de prestataires de paiement (type Mollie, SumUp, HelloAsso, etc.).</p>
		<p>Vous pouvez aussi indiquer le solde du compte à la date de début de l'exercice.</p>
		<p>Il sera possible de créer d'autres comptes plus tard, via le bouton «&nbsp;Modifier les comptes&nbsp;»</p>
	</div>

	<fieldset>
		<legend>Indiquer ici la liste des comptes bancaires</legend>
		<table class="auto list">
			<thead>
				<tr>
					<th>Nom du compte</th>
					<td>Solde du compte</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				{foreach from=$accounts item="account"}
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
		<p class="alert block hidden" id="no_accounts_msg">
			Aucun compte bancaire ne sera ajouté.
		</p>
	</fieldset>

	<p class="submit">
		{linkbutton label="Retour" shape="left" href=$back_url}
		{button type="submit" name="step" value="4" label="Étape suivante" shape="right" class="main"}
	</p>

{elseif $step === 4}

	<h2 class="step"><strong>4.</strong> Reporter le résultat de l'exercice précédent</h2>

	{if !$appropriation_account}
		<p class="alert block">Le plan comptable sélectionné ne contient aucun compte qui permet une affectation automatique du résultat. Le report de résultat n'est donc pas possible.</p>
	{else}
		<div class="help block">
			<p>Si vous aviez déjà réalisé une comptabilité auparavant, merci de reporter ci-dessous le résultat de l'exercice précédent.</p>
			<p>Si c'est votre premier exercice, laissez ce champ vide.</p>
		</div>

		<fieldset>
			<legend>Résultat précédent</legend>
			<dl>
				{input type="money" label="Résultat de l'exercice précédent" name="result"}
				{input type="radio" name="negative" value="0" label="Résultat excédentaire (positif)" default=0}
				{input type="radio" name="negative" value="1" label="Résultat déficitaire (négatif)"}
			</dl>

		</fieldset>
	{/if}

	<p class="submit">
		{csrf_field key=$csrf_key}
		{linkbutton label="Retour" shape="left" href=$back_url}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>
{/if}

</form>

<script type="text/javascript" src="{$admin_url}static/scripts/accounting_setup.js"></script>

{include file="_foot.tpl"}