{include file="admin/_head.tpl" title="Commencer un exercice" current="compta/exercices" js=1}

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Commencer un nouvel exercice</legend>
        <dl>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle}" required="required" /></dd>
            <dt><label for="f_debut">Début de l'exercice</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="debut" id="f_debut" value="{form_field name=debut}" size="10" required="required" /></dd>
            <dt><label for="f_fin">Fin de l'exercice</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="date" name="fin" id="f_fin" value="{form_field name=fin}" size="10" required="required" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_ajout_exercice"}
        <input type="submit" name="add" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}