{include file="_head.tpl" title="Journaux" current="config"}

{include file="config/_menu.tpl" current="advanced" sub_current="errors"}

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
			<h2 class="ruler">Occurence du {$report.context.date|date}</h2>
			<table class="list">
				{foreach from=$report.context key="k" item="v"}
				<tr>
					<th>{$k}</th>
					<td>{if $k == 'date'}{$v|date}{else}{$v}{/if}</td>
				</tr>
				{/foreach}
			</table>
		</article>
		{/foreach}
	</section>
{elseif isset($errors)}
	{if !count($errors)}
		<p class="block alert">Aucune erreur n'a été trouvée dans le journal error.log</p>
	{else}
		<table class="list">
			<thead>
				<tr>
					<th>Réf.</th>
					<td>Site</td>
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
					<td>{$error.hostname}</td>
					<td>
						{$error.message}<br />
						<tt>{$error.source}</tt>
					</td>
					<td>{$error.count}</td>
					<td>{$error.last_seen|date}</td>
					<td class="actions">
						{linkbutton shape="menu" label="Voir les détails" href="%s?type=errors&id=%s"|args:$self_url_no_qs,$ref}
					</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	{/if}
{/if}

{include file="_foot.tpl"}