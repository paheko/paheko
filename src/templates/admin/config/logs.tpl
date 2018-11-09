{include file="admin/_head.tpl" title="Journaux" current="config"}

{include file="admin/config/_menu.tpl" current="logs"}

<ul class="actions sub">
	<li{if $type != 'errors'} class="current"{/if}><a href="{$self_url_no_qs}">Actions utilisateurs</a></li>
	<li{if $type == 'errors'} class="current"{/if}><a href="?type=errors">Erreurs système</a></li>
</ul>

{if isset($reports) && isset($id)}
	{foreach from=$main.errors item="error"}
		<h2>{$error.type}: {$error.message} [Code: {$error.errorCode}]</h2>
		{if !empty($error.backtrace)}
			{foreach from=$error.backtrace item=trace}
				<h4>{$trace.function}{if !empty($trace.args)} ({$trace.args|count} arg.){/if}</h4>
				<h5>{$trace.file}:{$trace.line}</h5>
				{if !empty($trace.args)}
					<table>
					{foreach from=$trace.args key=name item=arg}
						<tr>
							<th>{$name}</th>
							<td>{$arg}</td>
						</tr>
					{/foreach}
					</table>
				{/if}
				{if !empty($trace.code)}
					<pre>{foreach from=$trace.code item=line key=n}{if $n == $trace.line}<b>{$line}</b>{else}{$line}{/if}<br />{/foreach}</pre>
				{/if}
			{/foreach}
		{/if}
	{/foreach}
	{foreach from=$reports item=report}
		<h2>Occurence du {$report.context.date|date_fr}</h2>

		<h3>Contexte</h3>
		<table>
			{foreach from=$report.context key="k" item="v"}
			<tr>
				<th>{$k}</th>
				<td>{$v}</td>
			</tr>
			{/foreach}
		</table>
	{/foreach}
{elseif isset($errors)}
	<p class="help">
		Liste des erreurs système et de code rencontrées par Garradin.
		Cliquer sur un des bugs pour le rapporter aux développeur⋅euses de Garradin.
	</p>

	{if !count($errors)}
		<p class="alert">Aucune erreur n'a été trouvée dans le journal error.log</p>
	{else}
		<table class="list">
			<thead>
				<tr>
					<th>Réf.</th>
					<td>Erreur</td>
					<td>Occurences</td>
					<td>Dernière fois</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				{foreach from=$errors item=error key=ref}
				<tr>
					<th><a href="?type=errors&id={$ref}">{$ref}</a></th>
					<td>
						{$error.message}<br />
						<tt>{$error.source}</tt>
					</td>
					<td>{$error.count}</td>
					<td>{$error.last_seen|date_fr}</td>
					<td class="actions"><a title="Voir les détails" class="icn" href="?type=errors&id={$ref}">𝍢</a></td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	{/if}
{else}
	<p class="help">
		Cette page permet de suivre les actions effectuées par les utilisateurs.
	</p>

	{if empty($list)}
		<p class="alert">
			Aucune entrée dans le journal d'actions.
		</p>
	{/if}
{/if}

{include file="admin/_foot.tpl"}