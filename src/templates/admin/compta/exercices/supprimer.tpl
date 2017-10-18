{include file="admin/_head.tpl" title="Supprimer un exercice" current="compta/exercices"}

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Supprimer un exercice</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer l'exercice «&nbsp;{$exercice.libelle}&nbsp;»
            du {$exercice.debut|date_fr:'d/m/Y'} au {$exercice.fin|date_fr:'d/m/Y'} ?
        </h3>
        <p class="help">
            Attention, l'exercice ne pourra pas être supprimé si des opérations y sont
            toujours affectées.
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_supprimer_exercice_%s"|args:$exercice.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}