{include file="admin/_head.tpl" title="Créer une page" current="wiki"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Informations</legend>
        <dl>
            <dt><label for="f_titre">Titre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="titre" id="f_titre" value="{form_field name=titre}" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="wiki_create"}
        <input type="submit" name="create" value="Créer cette page" />
    </p>

</form>


{include file="admin/_foot.tpl"}