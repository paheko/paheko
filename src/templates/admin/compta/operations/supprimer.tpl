{include file="admin/_head.tpl" title="Supprimer une opération" current="compta/gestion"}

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Supprimer cette opération ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer l'opération n°{$operation.id}
            «&nbsp;{$operation.libelle}&nbsp;» du {$operation.date|date_fr:'d/m/Y'} ?
        </h3>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_supprimer_%d"|args:$operation.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}