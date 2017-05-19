{include file="admin/_head.tpl" title="Clôturer un exercice" current="compta/exercices" js=1}

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Clôturer un exercice</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir clôturer l'exercice «&nbsp;{$exercice.libelle}&nbsp;» ?
        </h3>
        <p class="help">
            Attention, une fois clôturé, les opérations de cet exercice ne pourront plus être supprimées ou modifiées.
        </p>
        <dl>
            <dt>Début de l'exercice</dt>
            <dd>{$exercice.debut|date_fr:'d/m/Y'}</dd>
            <dt><label for="f_fin">Fin de l'exercice</label></dt>
            <dd class="help">Si des opérations existent après cette date, elles seront automatiquement
                attribuées à un nouvel exercice.</dd>
            <dd><input type="date" name="fin" id="f_fin" value="{form_field name=fin default=$exercice.fin|date_fr:'Y-m-d'}" size="10" /></dd>
            <dt>
                <input type="checkbox" name="reports" {form_field name=reports default="1" checked=true} id="f_reports" /> <label for="f_reports">Exécuter automatiquement les reports à nouveau</label>
            </dt>
            <dd class="help">Les soldes créditeurs et débiteurs de chaque compte seront reportés 
                automatiquement dans le nouvel exercice. Si vous ne cochez pas la case, vous devrez faire les reports à nouveau vous-même.</dd>
        </h3>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_cloturer_exercice_%s"|args:$exercice.id}
        <input type="submit" name="close" value="Clôturer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}