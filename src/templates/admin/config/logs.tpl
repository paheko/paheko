{include file="admin/_head.tpl" title="Journaux" current="config" custom_css=["styles/config.css"]}

{include file="admin/config/_menu.tpl" current="logs"}

{if ERRORS_ENABLE_LOG_VIEW}
<nav class="tabs">
	<ul class="sub">
		<li{if $type != 'errors'} class="current"{/if}><a href="{$self_url_no_qs}">Actions utilisateurs</a></li>
		<li{if $type == 'errors'} class="current"{/if}><a href="?type=errors">Erreurs système</a></li>
	</ul>
</nav>
{/if}

{if isset($reports) && isset($id)}
	<section class="error">
		{foreach from=$main.errors item="error"}
			<h2 class="ruler">{$error.type}: {$error.message} [Code: {$error.errorCode}]</h2>
			{if !empty($error.backtrace)}
				{foreach from=$error.backtrace item=trace}
				<article class="trace">
					{if $trace.function}
						<h4>
							{$trace.function}
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
						</h4>
					{/if}
					{if $trace.file}<h5>{$trace.file}:{$trace.line}</h5>{/if}
					{if !empty($trace.code)}
						<pre>{foreach from=$trace.code item=line key=n}{if $n == $trace.line}<b>{/if}<i>{$n}</i> {$line}{if $n == $trace.line}</b>{/if}<br />{/foreach}</pre>
					{/if}
				</article>
				{/foreach}
			{/if}
		{/foreach}

		{foreach from=$reports item=report}
		<article class="event">
			<h2 class="ruler">Occurence du {$report.context.date|date_fr}</h2>
			<table class="list">
				{foreach from=$report.context key="k" item="v"}
				<tr>
					<th>{$k}</th>
					<td>{if $k == 'date'}{$v|date_fr}{else}{$v}{/if}</td>
				</tr>
				{/foreach}
			</table>
		</article>
		{/foreach}
	</section>
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