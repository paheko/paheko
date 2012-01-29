{include file="admin/_head.tpl" title="Supprimer une catégorie" current="membres/categories"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Supprimer la catégorie de membres sélectionnée ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer cette catégorie de membres ?
        </h3>
        <p class="help">
            Attention, la catégorie ne doit plus contenir de membres pour pouvoir
            être supprimée.
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="delete_cat_"|cat:$cat.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}