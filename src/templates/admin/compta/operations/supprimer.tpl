{include file="admin/_head.tpl" title="Supprimer une opération" current="compta/gestion"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Supprimer cette opération ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer l'opération n°{$operation.id|escape}
            «&nbsp;{$operation.libelle|escape}&nbsp;» du {$operation.date|date_fr:'d/m/Y'} ?
        </h3>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_supprimer_`$operation.id`"}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}