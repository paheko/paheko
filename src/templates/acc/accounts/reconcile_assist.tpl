{include file="admin/_head.tpl" title="Rapprochement : %s — %s"|args:$account.code,$account.label current="acc/accounts" js=1}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<ul>
		<li><a href="{$admin_url}acc/accounts/reconcile.php?id={$account.id}">Rapprochement manuel</a></li>
		<li class="current"><a href="{$admin_url}acc/accounts/reconcile_assist.php?id={$account.id}">Rapprochement assisté</a></li>
	</ul>
</nav>

{form_errors}

<form method="post" action="{$self_url}" enctype="multipart/form-data">
	{if !$csv->loaded()}
		<fieldset>
			<legend>Relevé de compte</legend>
			<p class="help block">
				Le rapprochement assisté permet de s'aider d'un relevé de compte au format CSV pour trouver les écritures manquantes ou erronées.<br />
				<a href="https://fossil.kd2.org/garradin/wiki?name=Compta/Rapprochement_assist%C3%A9" target="_blank">Aide détaillée</a>
			</p>
			<dl>
				{include file="common/_csv_help.tpl"}
				{input type="file" name="file" label="Fichier CSV" accept=".csv,text/csv" required=1}
			</dl>
			<p class="submit">
				{csrf_field key=$csrf_key}
				{button type="submit" name="upload" label="Envoyer le fichier" class="main" shape="upload"}
			</p>
		</fieldset>
	{elseif !$csv->ready()}
		{include file="common/_csv_match_columns.tpl"}
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="assign" label="Continuer" class="main" shape="right"}
		</p>
	{else}
		<fieldset>
			<legend>Relevé de compte</legend>
			<dl>
				<dt>
					Nombre de lignes
				</dt>
				<dd>
					{$csv->count()}
				</dd>
				<dt>
					Période couverte
				</dt>
				<dd>
					Du {$start|date_short} au {$end|date_short}
				</dd>
			</dl>
			<p class="submit">
				{csrf_field key=$csrf_key}
				{button type="submit" name="cancel" value="1" label="Annuler" shape="left"}
			</p>
		</fieldset>
	{/if}
</form>

{if !empty($lines)}
	<p class="block help">
		Les écritures apparaissent ici dans le sens du relevé de banque, à l'inverse des journaux comptables.
	</p>

	<form method="post" action="{$self_url}">
		<table class="list">
			<thead>
				<tr>
					<th colspan="7">Journal du compte (compta)</th>
					<td class="separator">Correspondance</td>
					<th colspan="5" class="separator">Extrait de compte (banque)</th>
				</tr>
				<tr>
					<td class="check"><input type="checkbox" title="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
					<td></td>
					<td>Date</td>
					<td class="money">Débit</td>
					<td class="money">Crédit</td>
					<td class="money">Solde cumulé</td>
					<th>Libellé</th>
					<td></td>
					<td class="separator">Date</td>
					<th>Libellé</th>
					<td class="money">Débit</td>
					<td class="money">Crédit</td>
					<td class="money">Solde cumulé</td>
				</tr>
			</thead>
			<tbody>
				{foreach from=$lines item="line"}
				{if isset($line->journal->sum)}
				<tr>
					<td colspan="5"></td>
					<td class="money">{if $line.sum > 0}-{/if}{$line.sum|abs|raw|html_money:false}</td>
					<th>Solde au {$line.date|date_fr:'d/m/Y'}</th>
					<td colspan="2"></td>
				</tr>
				{else}
				<tr>
					{if isset($line->journal)}
						<td class="check">
							{input type="checkbox" name="reconcile[%d]"|args:$line.journal.id_line value="1" default=$line.journal.reconciled}
						</td>
						<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.journal.id}">#{$line.journal.id}</a></td>
						<td>{$line.journal.date|date_short}</td>
						<td class="money">{$line.journal.credit|raw|html_money}</td>
						<td class="money">{$line.journal.debit|raw|html_money}</td> {* Not a bug! Credit/debit is reversed here to reflect the bank statement *}
						<td class="money">{if $line.journal.running_sum > 0}-{/if}{$line.journal.running_sum|abs|raw|html_money:false}</td>
						<th>{$line.journal.label}</th>
					{else}
						<td colspan="7"></td>
					{/if}
						<td class="separator">
						{if $line->journal && $line->csv}
							==
						{else}
							<b class="icn">⚠</b>
						{/if}
						</td>
					{if isset($line->csv)}
						<td class="separator">{$line.csv.date|date_short}</td>
						<th>{$line.csv.label}</th>
						<td class="money">{$line.csv.debit|raw|html_money}</td>
						<td class="money">{$line.csv.credit|raw|html_money}</td>
						<td class="money">{$line.csv.running_sum|raw|html_money}</td>
					{else}
						<td colspan="5" class="separator"></td>
					{/if}
				</tr>
				{/if}
			{/foreach}
			</tbody>
		</table>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="save" label="Enregistrer" class="main" shape="check"}
		</p>
	</form>
{/if}

{include file="admin/_foot.tpl"}