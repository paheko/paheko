
<section class="web preview">
	<header>
		<h1 class="ruler">{$page.title}</h1>
	{if isset($version)}
		<h3>Version du {$version.date|date_short:true}</h3>
	{else}
		<h3>Historique des modifications</h3>
	{/if}
		<p class="actions">
			{if isset($version)}
				{linkbutton shape="left" label="Retour" href="?id=%d&history=list"|args:$page.id}
			{else}
				{linkbutton shape="left" label="Retour" href="?id=%d"|args:$page.id}
			{/if}
		</p>
	</header>

	{if isset($versions)}
		{if $versions->count()}
			{include file="common/dynamic_list_head.tpl" list=$versions}
			{foreach from=$versions->iterate() item="version"}
				<tr>
					<th>{$version.date|date_short:true}</th>
					<td>{if !$version.author}<em>Membre supprimé</em>{else}{$version.author}{/if}</td>
					<td>
						{$version.size} caractères
					</td>
					<td>
						{if $version.changes < 0}
							<b class="error">{$version.changes}</b>
						{else}
							<b class="confirm">+{$version.changes}</b>
						{/if}
					</td>
					<td class="actions">
						{linkbutton shape="menu" label="Modifications" href="?id=%d&history=%d"|args:$page.id:$version.id}
						{linkbutton shape="reload" label="Restaurer" href="!web/edit.php?id=%d&restore=%d"|args:$page.id:$version.id}
					</td>
				</tr>
			{/foreach}
			</tbody>
			</table>
		{else}
			<p class="alert block">Aucun historique n'a été trouvé pour cette page.</p>
		{/if}
	{elseif isset($version)}
		{diff old=$version.previous_content new=$version.content context=true}
		<article>
			{$page->preview($version.content)|raw}
		</article>
	{/if}
</section>