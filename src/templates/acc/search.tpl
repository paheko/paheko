{include file="admin/_head.tpl" title="Recherche" current="acc" custom_js=['query_builder.min.js']}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$self_url}">Recherche</a></li>
		<li><a href="saved_searches.php">Recherches enregistrées</a></li>
	</ul>
</nav>

{include file="common/search/advanced.tpl" action_url=$self_url}

{if !empty($result)}
	{*if $session->canAccess('compta', Membres::DROIT_ECRITURE)}
		<form method="post" action="{$admin_url}membres/action.php" class="memberList">
	{/if*}

	<p class="help">{$result|count} écritures trouvées pour cette recherche.</p>
	<table class="list search">
		<thead>
			<tr>
				{*if $session->canAccess('membres', Membres::DROIT_ADMIN)}<td class="check"><input type="checkbox" value="Tout cocher / décocher" /></td>{/if*}
				{foreach from=$result_header item="label"}
					<td>{$label}</td>
				{/foreach}
				<td></td>
			</tr>
		</thead>
		<tbody>
			{foreach from=$result item="row"}
				<tr>
					{*if $session->canAccess('membres', Membres::DROIT_ADMIN)}<td class="check"><input type="checkbox" name="selected[]" value="{$row.id}" /></td>{/if*}
					{foreach from=$row key="key" item="value"}
						{if $key == 'transaction_id'}
						<td class="num">
							<a href="{$admin_url}acc/transactions/details.php?id={$value}">{$value}</a>
						</td>
						{else}
						<td>
							{if $key == 'credit' || $key == 'debit'}
								{$value|raw|html_money:false}
							{elseif null == $value}
								<em>(nul)</em>
							{else}
								{$value}
							{/if}
						</td>
						{/if}
					{/foreach}
					<td class="actions">
						{if $row.transaction_id}
						{linkbutton shape="search" label="Détails" href="!acc/transactions/details.php?id=%d"|args:$row.transaction_id}
						{/if}
					</td>
				</tr>
			{/foreach}
		</tbody>
	{*if $session->canAccess('membres', Membres::DROIT_ADMIN)}
		{include file="admin/membres/_list_actions.tpl" colspan=count($result_header)+1}
	{/if*}
	</table>

	{*if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
		</form>
	{/if*}

{elseif $result !== null}

	<p class="block alert">
		Aucun résultat trouvé.
	</p>

	</form>
{/if}


{include file="admin/_foot.tpl"}