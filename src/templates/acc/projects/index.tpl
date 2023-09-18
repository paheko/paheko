{include file="_head.tpl" title="Projets" current="acc/years"}

{include file="./_nav.tpl" current='index'}

<div class="year-header">
	<h2>{$config.org_name} — Projets</h2>

{if $projects_count}
	<p class="noprint">
		{if $by_year}
			{linkbutton href="./" label="Grouper par projet" shape="left"}
		{else}
			{linkbutton href="?by_year=1" label="Grouper par exercice" shape="right"}
		{/if}
		{linkbutton href="!acc/reports/ledger.php?project=all" label="Grand livre analytique — tous les exercices"}
	</p>


	<p class="noprint print-btn">
		<button onclick="window.print(); return false;" class="icn-btn" data-icon="⎙">Imprimer</button>
		{if PDF_COMMAND}
			{linkbutton shape="download" href="%s?by_year=%d&_pdf"|args:$self_url_no_qs,$by_year label="Télécharger en PDF"}
		{/if}
	</p>

{/if}
</div>

{if $projects_count}

	{include file="./_list.tpl"}

{else}
	<p class="block alert">Il n'existe pas encore de projet. {linkbutton label="Créer un nouveau projet" href="edit.php" shape="plus" target="_dialog"}</p>
	<p class="help">Les projets (aussi appelés comptabilité analytique) permettent de suivre le budget d'une activité ou d'un projet. {linkbutton shape="help" label="Aide sur les projets" target="_dialog" href=$help_pattern_url|args:"comptabilite-analytique"}</p>
{/if}

{include file="_foot.tpl"}