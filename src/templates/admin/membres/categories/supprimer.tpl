{include file="admin/_head.tpl" title="Supprimer une catégorie" current="membres/categories"}

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Supprimer la catégorie de membres ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer la catégorie «&nbsp;{$cat.nom}&nbsp;» ?
        </h3>
        <p class="help">
            Attention, la catégorie ne doit plus contenir de membres pour pouvoir
            être supprimée.
        </p>
        <p class="help">
            Notez que si des pages du wiki étaient restreintes à la lecture ou à l'écriture
            aux seuls membres de ce groupe, elles redeviendront lisibles et modifiables
            par tous les membres ayant accès au wiki !
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="delete_cat_"|cat:$cat.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}