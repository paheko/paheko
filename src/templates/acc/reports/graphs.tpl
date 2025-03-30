{include file="_head.tpl" title="%sGraphiques"|args:$title current="acc/years"  prefer_landscape=true}

{include file="acc/reports/_header.tpl" current="graphs" title="Graphiques" allow_filter=false}

{if $nb_transactions < 3}
	<p class="alert block">Il n'y a pas encore suffisamment d'écritures dans cet exercice pour pouvoir afficher les statistiques.</p>
{else}
	<section class="year-infos">
		<section class="graphs">
			{foreach from=$graphs key="url" item="label"}
			<figure>
				<img src="{$url|args:$criterias_query}" alt="" />
				<figcaption>{$label}</figcaption>
			</figure>
			{/foreach}
		</section>
	</section>

	<p class="help">
		En raison des arrondis, la somme des pourcentages peut ne pas être égale à 100%.
	</p>
{/if}

{include file="_foot.tpl"}