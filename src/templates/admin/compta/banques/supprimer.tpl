{include file="admin/_head.tpl" title="Supprimer un compte" current="compta/banques"}

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Supprimer le compte ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer le compte «&nbsp;{$compte.id} - {$compte.libelle}&nbsp;» ?
        </h3>
        <p class="help">
            Attention, le compte ne pourra pas être supprimé si des opérations y sont
            affectées.
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_delete_banque_%s"|args:$compte.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}