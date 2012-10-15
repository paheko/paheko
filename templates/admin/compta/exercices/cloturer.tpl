{include file="admin/_head.tpl" title="Clôturer un exercice" current="compta/exercices"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Clôturer un exercice</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir clôturer l'exercice «&nbsp;{$exercice.libelle|escape}&nbsp;»
            du {$exercice.debut|date_fr:'d/m/Y'} au {$exercice.fin|date_fr:'d/m/Y'} ?
        </h3>
        <p class="help">
            Attention, les opérations de cet exercice ne pourront plus être supprimées ou modifiées.
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_cloturer_exercice_`$exercice.id`"}
        <input type="submit" name="close" value="Clôturer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}