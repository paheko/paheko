{include file="admin/_head.tpl" title="Rapprochement : %s — %s"|args:$account.code,$account.label current="acc/accounts"}

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
			{button type="submit" name="cancel" value="1" label="Annuler" shape="left"}
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
					<th colspan="6">Journal du compte (compta)</th>
					<td class="separator"></td>
					<th colspan="4" class="separator">Extrait de compte (banque)</th>
				</tr>
				<tr>
					<td class="check"><input type="checkbox" title="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
					<td></td>
					<td>Date</td>
					<td class="money">Mouvement</td>
					<td class="money">Solde cumulé</td>
					<th style="text-align: right">Libellé</th>
					<td class="separator"></td>
					<th class="separator">Libellé</th>
					<td class="money">Mouvement</td>
					<td class="money">Solde cumulé</td>
					<td>Date</td>
				</tr>
			</thead>
			<tbody>
				{foreach from=$lines key="line_id" item="line"}
				{if isset($line->journal->sum)}
				<tr>
					<td colspan="4"></td>
					<td class="money">{if $line.journal.sum > 0}-{/if}{$line.journal.sum|abs|raw|html_money:false}</td>
					<th style="text-align: right">Solde au {$line.journal.date|date_short}</th>
					<td class="separator"></td>
					<td class="separator"></td>
					<td colspan="3"></td>
				</tr>
				{else}
				<tr>
					{if isset($line->journal)}
						<td class="check">
							{input type="checkbox" name="reconcile[%d]"|args:$line.journal.id_line value="1" default=$line.journal.reconciled}
						</td>
						<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.journal.id}">#{$line.journal.id}</a></td>
						<td>{$line.journal.date|date_short}</td>
						<td class="money">
							{if $line.journal.credit}
								{* Not a bug! Credit/debit is reversed here to reflect the bank statement *}
								-{$line.journal.credit|raw|html_money}
							{else}
								{$line.journal.debit|raw|html_money}
							{/if}
						</td>
						<td class="money">{if $line.journal.running_sum > 0}-{/if}{$line.journal.running_sum|abs|raw|html_money:false}</td>
						<th style="text-align: right">{$line.journal.label}</th>
					{else}
						<td colspan="5"></td>
						<td style="text-align: right">
							{if $line.add}
							{* FIXME later add ability to pre-fill multi-line transactions in new.php
								{linkbutton label="Créer cette écriture" target="_blank" href="%s&create=%s"|args:$self_url,$line_id shape="plus"}
							*}
							{/if}
						</td>
					{/if}
						<td class="separator">
						{if $line->journal && $line->csv}
							==
						{else}
							<b class="icn">⚠</b>
						{/if}
						</td>
					{if isset($line->csv)}
						<th class="separator">{$line.csv.label}</th>
						<td class="money">
							{$line.csv.amount|raw|html_money}
						</td>
						<td class="money">{$line.csv.running_sum|raw|html_money}</td>
						<td>{$line.csv.date|date_short}</td>
					{else}
						<td colspan="4" class="separator"></td>
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