<div class="year-header">

    <nav class="tabs noprint">
        <ul>
            <li{if $current == "journal"} class="current"{/if}><a href="{$admin_url}acc/reports/journal.php?year={$year.id}">Journal général</a></li>
            <li{if $current == "ledger"} class="current"{/if}><a href="{$admin_url}acc/reports/ledger.php?year={$year.id}">Grand livre</a></li>
            <li{if $current == "statement"} class="current"{/if}><a href="{$admin_url}acc/reports/statement.php?year={$year.id}">Compte de résultat</a></li>
            <li{if $current == "balance_sheet"} class="current"{/if}><a href="{$admin_url}acc/reports/balance_sheet.php?year={$year.id}">Bilan</a></li>
        </ul>
    </nav>

    <h2>{$config.nom_asso}</h2>
    {if isset($projet)}
        <h3>Projet&nbsp;: {$projet.libelle}</h3>
    {else}
        <p>Exercice comptable {if $year.closed}clôturé{else}en cours{/if} du
            {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'}, généré le {$close_date|date_fr:'d/m/Y'}</p>
    {/if}

	<p class="noprint">
		<button onclick="window.print(); return false;" class="icn-btn" data-icon="⎙">Imprimer</button>
	</p>
</div>
