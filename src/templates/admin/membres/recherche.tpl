{include file="admin/_head.tpl" title="Recherche de membre" current="membres" js=1 custom_js=['query_builder.min.js']}

{include file="admin/membres/_nav.tpl" current="recherche"}

{include file="common/search/advanced.tpl" action_url=$self_url}

{if !empty($result)}
	{if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
		<form method="post" action="{$admin_url}membres/action.php" class="memberList">
	{/if}

	<p class="help">{$result|count} membres trouvés pour cette recherche.</p>
	<table class="list search">
		<thead>
			<tr>
				{if $session->canAccess('membres', Membres::DROIT_ADMIN)}<td class="check"><input type="checkbox" value="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>{/if}
				{foreach from=$result_header key="c" item="cfg"}
					<td>{$cfg.title}</td>
				{/foreach}
				<td></td>
			</tr>
		</thead>
		<tbody>
			{foreach from=$result item="row"}
				<tr>
					{if $session->canAccess('membres', Membres::DROIT_ADMIN)}<td class="check">{input type="checkbox" name="selected[]" value=$row.id}</td>{/if}
					{foreach from=$row key="key" item="value"}
						<?php $link = false; ?>
						{if isset($result_header[$key])}
							<td>
								{if !$link}
									<a href="{$admin_url}membres/fiche.php?id={$row.id}">
								{/if}

								{$value|raw|display_champ_membre:$key}

								{if !$link}
									<?php $link = true; ?>
									</a>
								{/if}
							</td>
						{/if}
					{/foreach}
					<td class="actions">
						{linkbutton shape="user" label="Fiche membre" href="!membres/fiche.php?id=%d"|args:$row.id}
						{if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
						{linkbutton shape="edit" label="Modifier" href="!membres/modifier.php?id=%d"|args:$row.id}
						{/if}
					</td>
				</tr>
			{/foreach}
		</tbody>
	{if $session->canAccess('membres', Membres::DROIT_ADMIN)}
		{include file="admin/membres/_list_actions.tpl" colspan=count($result_header)+1}
	{/if}
	</table>

	{if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
		</form>
	{/if}

{elseif $result !== null}

	<p class="block alert">
		Aucun membre trouvé.
	</p>

	</form>
{/if}


{include file="admin/_foot.tpl"}