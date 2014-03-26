{include file="admin/_head.tpl" title="Modifier un exercice" current="compta/exercices" js=1}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Modifier un exercice</legend>
        <dl>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle data=$exercice}" required="required" /></dd>
            <dt><label for="f_debut">Début de l'exercice</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="debut" id="f_debut" value="{form_field name=debut default=$exercice.debut|date_fr:'Y-m-d'}" size="10" required="required" /></dd>
            <dt><label for="f_fin">Fin de l'exercice</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="fin" id="f_fin" value="{form_field name=fin default=$exercice.fin|date_fr:'Y-m-d'}" size="10" required="required" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_modif_exercice_`$exercice.id`"}
        <input type="submit" name="edit" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}