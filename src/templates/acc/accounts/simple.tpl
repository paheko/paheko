<?php use Paheko\Entities\Accounting\Transaction; ?>
{include file="_head.tpl" title="Suivi : %s"|args:$types[$type] current="acc/simple"}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<aside>
	{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		{exportmenu href="?type=%d"|args:$type}
	{/if}
		{linkbutton shape="search" href="!acc/search.php?year=%d&type=%d"|args:$year.id,$type label="Recherche"}
	</aside>
	<ul>
		{foreach from=$types key="key" item="label"}
		<li{if $type == $key} class="current"{/if}><a href="?type={$key}">{$label}</a></li>
		{/foreach}
	</ul>
</nav>

{if $pending_count}
	{include file="acc/transactions/_pending_message.tpl"}
{/if}

{if !$list->count()}
	<p class="alert block">
		Aucune écriture à afficher.
	</p>
{else}
	<form method="post" action="{$admin_url}acc/transactions/actions.php">
	{assign var="has_debt_or_credit" value=false}

	{include file="common/dynamic_list_head.tpl" check=$can_edit}

		{foreach from=$list->iterate() item="line"}
			<tr>
				{if $can_edit}
				<td class="check">
					{input type="checkbox" name="check[%s]"|args:$line.id_line value=$line.id default=0}
				</td>
				{/if}
				{if $line.type_label}
				<td>{$line.type_label}</td>
				{/if}
				<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">#{$line.id}</a></td>
				<td>{$line.date|date_short}</td>
				<td class="money">{$line.change|abs|raw|money}</td>
				<td>{$line.reference}</td>
				<th>{$line.label}</th>
				<td>{$line.line_reference}</td>
				<td class="num">{foreach from=$line.project_code item="code" key="id"}<a href="{$admin_url}acc/reports/statement.php?project={$id}&amp;year={$year.id}">{$code}</a> {/foreach}</td>
				{if isset($line.locked)}
				<td>{if $line.locked}{icon title="Écriture verrouillée" shape="lock"}{/if}</td>
				{/if}
				<td class="num">{if $line.files}{$line.files}{/if}</td>
				{if property_exists($line, 'status_label')}
				<td>
					{if $line.status & Entities\Accounting\Transaction::STATUS_WAITING}
						<span class="alert">{$line.status_label}</span>
					{else}
						<span class="confirm">{$line.status_label}</span>
					{/if}
				</td>
				{/if}
				<td class="actions">
					{if $line.type == Transaction::TYPE_DEBT && ($line.status & Transaction::STATUS_WAITING)}
						{assign var="has_debt_or_credit" value=true}
						{linkbutton shape="check" label="Régler cette dette" href="!acc/transactions/new.php?payoff=%d"|args:$line.id}
					{elseif $line.type == Transaction::TYPE_CREDIT && ($line.status & Transaction::STATUS_WAITING)}
						{assign var="has_debt_or_credit" value=true}
						{linkbutton shape="export" label="Régler cette créance" href="!acc/transactions/new.php?payoff=%d"|args:$line.id}
					{/if}

					{linkbutton href="!acc/transactions/details.php?id=%d"|args:$line.id label="Détails" shape="search"}
				</td>
			</tr>
		{/foreach}
		</tbody>
		{if $can_edit}
			<tfoot>
			<tr>
				<td class="check"><input type="checkbox" value="Tout cocher / décocher" id="f_all2" /><label for="f_all2"></label></td>
				<td class="actions" colspan="10">
					<em>Pour les écritures cochées :</em>
					<input type="hidden" name="from" value="{$self_url}" />
					<input type="hidden" name="year" value="{$year.id}" />
					{csrf_field key="projects_action"}
					<select name="action">
						<option value="">— Choisir une action à effectuer —</option>
						{if $has_debt_or_credit}
							<option value="payoff">Régler ces dettes</option>
						{elseif $type == Transaction::TYPE_CREDIT}
							<option value="payoff">Régler ces créances</option>
						{elseif $has_debt_or_credit}
							<option value="payoff">Régler ces dettes ou créances</option>
						{/if}
						<option value="change_project">Ajouter/enlever d'un projet</option>
						<option value="delete">Supprimer les écritures</option>
					</select>
					<noscript>
						{button type="submit" value="OK" shape="right" label="Valider"}
					</noscript>
				</td>
			</tr>
		</tfoot>
		{/if}
	</table>

	</form>

	{$list->getHTMLPagination()|raw}
{/if}

{include file="_foot.tpl"}