<div class="exercice">
    <h2>{$config.nom_asso}</h2>
    {if isset($projet)}
        <h3>Projet&nbsp;: {$projet.libelle}</h3>
    {else}
        <p>Exercice comptable {if $year.closed}clôturé{else}en cours{/if} du
            {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'}, généré le {$close_date|date_fr:'d/m/Y'}</p>
    {/if}

	<p class="noprint">
		<button onclick="window.print(); return false;">
			<b href="#need_js" class="action icn print">⎙</b>
			Imprimer
		</button>
	</p>
</div>

