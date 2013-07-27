{include file="admin/_head.tpl" title="Supprimer une catégorie" current="compta/categories"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Supprimer la catégorie comptable ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer la catégorie «&nbsp;{$cat.intitule|escape}&nbsp;» ?
        </h3>
        <p class="help">
            Attention, la catégorie ne pourra pas être supprimée si des opérations y sont
            affectées.
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="delete_compta_cat_"|cat:$cat.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}