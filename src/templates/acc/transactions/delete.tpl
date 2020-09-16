{include file="admin/_head.tpl" title="Supprimer l'écriture n°%d"|args:$transaction.id current="acc"}

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Supprimer cette écriture ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer l'opération n°{$transaction.id}
            «&nbsp;{$transaction.label}&nbsp;» du {$transaction.date|date_fr:'d/m/Y'} ?
        </h3>
    </fieldset>

    <p class="submit">
        {csrf_field key="acc_delete_%d"|args:$transaction.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}