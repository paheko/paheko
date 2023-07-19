
<table class="schema list auto {$class}">
	<caption>
		<strong><tt>{$table.name}</tt></strong>
		{if $table.comment} <small><em>({$table.comment})</em></small>{/if}
	</caption>
	<thead>
		<tr>
		{if $indexes !== null}
			<td>Index</td>
		{/if}
			<th>Colonne</th>
			<td>Type</td>
			<td>Nul&nbsp;?</td>
			<td>Valeur par défaut</td>
			<td>Référence</td>
			<td>Commentaire</td>
		</tr>
	</thead>
	<tbody>
	{foreach from=$table.columns item="column"}
		<tr>
		{if $indexes !== null}
			<td>
				{if $column.pk}<a class="num">P</a>{/if}
				{foreach from=$indexes key="i" item="idx"}
					{if array_key_exists($column.name, $idx.columns)}
						<a class="num">{$i}{if $idx.unique}<sup>U</sup>{/if}</a>
					{/if}
				{/foreach}
			</td>
		{/if}
			<th>{$column.name}</th>
			<td>{if $column.type}{$column.type}{else}<em>Dynamique</em>{/if}</td>
			<td>{if $column.notnull}{else}Oui{/if}</td>
			<td>{if $column.dflt_value !== null}<tt>{$column.dflt_value}</tt>{elseif !$column.notnull}<em>NULL</em>{else}<em>Aucune</em>{/if}</td>
			<td>
				{if !empty($column.fk)}
					&rarr;
					{if !empty($fk_link)}
						<a href="?table_info={$column.fk.table}">{$column.fk.table}</a>
					{else}
						{$column.fk.table}
					{/if}
					({$column.fk.to})
				{/if}
			</td>
			<td class="comment">{$column.comment}</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{if $indexes}
<h2 class="ruler">Liste des index</h2>
<table class="schema list auto {$class}">
	<thead>
		<tr>
			<td>Num.</td>
			<th>Nom</th>
			<td>Type</td>
			<td>Colonnes</td>
		</tr>
	</thead>
	<tbody>
	{foreach from=$indexes item="idx" key="i"}
		<tr>
			<td><a class="num">{$i}</a></td>
			<th>{$idx.name}</th>
			<td>{if $idx.unique}Unique{/if}</td>
			<td><ul>{foreach from=$idx.columns item="c"}<li>{$c.name}</li>{/foreach}</ul></td>
		</tr>
	{/foreach}
	</tbody>
</table>
{/if}