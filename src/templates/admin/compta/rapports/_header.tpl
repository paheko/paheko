<div class="exercice">
    <h2>{$config.nom_asso}</h2>
    {if isset($projet)}
        <h3>Projet&nbsp;: {$projet.libelle}</h3>
    {else}
        <p>Exercice comptable {if $exercice.cloture}clôturé{else}en cours{/if} du
            {$exercice.debut|date_fr:'d/m/Y'} au {$exercice.fin|date_fr:'d/m/Y'}, généré le {$cloture|date_fr:'d/m/Y'}</p>
    {/if}
</div>
