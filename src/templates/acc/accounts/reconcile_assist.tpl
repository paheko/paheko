{include file="_head.tpl" title="Rapprochement : %s — %s"|args:$account.code,$account.label current="acc/accounts"}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<ul>
		<li><a href="{$admin_url}acc/accounts/reconcile.php?id={$account.id}">Rapprochement manuel</a></li>
		<li class="current"><a href="{$admin_url}acc/accounts/reconcile_assist.php?id={$account.id}">Rapprochement assisté</a></li>
	</ul>
</nav>

{if $_GET.msg === 'OK'}
<p class="confirm block">
	Le rapprochement a bien été enregistré.
</p>
{/if}

{form_errors}

<p class="help">
	Le rapprochement assisté permet de s'aider d'un relevé de compte pour trouver les écritures manquantes ou erronées.<br />
	{linkbutton shape="help" href=$help_pattern_url|args:"rapprochement-assiste" target="_dialog" label="Aide détaillée"}
</p>
<form method="post" action="{$self_url}" enctype="multipart/form-data">
	{if !$csv->loaded()}
		<fieldset>
			<legend>Relevé de compte</legend>
			<dl>
				{input type="file" name="file" label="Fichier à importer" accept="csv" required=1}
				{include file="common/_csv_help.tpl" more_text="Le fichier doit obligatoirement disposer, soit d'une colonne 'Montant', soit de deux colonnes 'Débit' et 'Crédit'."}
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
			</dl>
			<p class="submit">
				{csrf_field key=$csrf_key}
				{button type="submit" name="cancel" value="1" label="Annuler le rapprochement" shape="left"}
			</p>
		</fieldset>
	</form>

	<form method="get" action="">
		<fieldset>
			<legend>Période de rapprochement</legend>
			<dl>
				<dd>
					Du
					{input type="date" name="start" default=$start}
					au
					{input type="date" name="end" default=$end}
				</dd>
			</dl>
			<p class="submit">
				<input type="hidden" name="id" value="{$account.id}" />
				{button type="submit" label="Modifier" shape="right"}
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
					<td class="money">{if $line.journal.sum > 0}-{/if}{$line.journal.sum|abs|raw|money:false}</td>
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
								-{$line.journal.credit|raw|money}
							{else}
								{$line.journal.debit|raw|money}
							{/if}
						</td>
						<td class="money">{if $line.journal.running_sum > 0}-{/if}{$line.journal.running_sum|abs|raw|money:false}</td>
						<th style="text-align: right">{$line.journal.label}</th>
					{else}
						<td colspan="5"></td>
						<td class="actions">
							{if $line.add}
								{linkbutton label="Saisir cette écriture" target="_dialog" href="!acc/transactions/new.php?%s"|args:$line.csv.new_params shape="plus"}
							{/if}
						</td>
					{/if}
						<td class="separator">
						{if $line->journal && $line->csv}
							==
						{else}
							{icon shape="alert"}
						{/if}
						</td>
					{if isset($line->csv)}
						<th class="separator">{$line.csv.label}</th>
						<td class="money">
							{$line.csv.amount|raw|money:true:true}
						</td>
						<td class="money">{if $line.csv.balance}{$line.csv.balance|raw|money}{else}{$line.csv.running_sum|raw|money}{/if}</td>
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

{include file="_foot.tpl"}