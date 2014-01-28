{include file="admin/_head.tpl" title="Modifier une activité" current="membres/transactions/admin" js=1}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/transactions/gestion/">Cotisations et activités</a></li>
    <li><a href="{$admin_url}membres/transactions/gestion/rappels.php">Gestion des rappels</a></li>
    <li><a href="{$admin_url}membres/transactions/rappels.php">État des rappels</a></li>
</ul>

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Modifier une activité ou cotisation</legend>
        <dl>
            <dt><label for="f_intitule">Intitulé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="intitule" id="f_intitule" value="{form_field name=intitule data=$transaction}" /></dd>
            <dt><label for="f_description">Description</label></dt>
            <dd><textarea name="description" id="f_description" cols="50" rows="3">{form_field name=description data=$transaction}</textarea></dd>
            <dt><label for="f_montant">Montant</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" name="montant" step="0.01" min="0.00" id="f_montant" value="{form_field default=20 name=montant default=0.00 data=$transaction}" /></dd>

            <dt><label for="f_periodicite_jours">Période de validité</label></dt>
            <dd><input type="radio" name="periodicite" id="f_periodicite_ponctuel" value="ponctuel" {form_field checked="ponctuel" name=periodicite default="ponctuel" data=$transaction} /> <label for="f_periodicite_ponctuel">Pas de période (activité ou cotisation ponctuelle)</label></dd>

            <dd><input type="radio" name="periodicite" id="f_periodicite_jours" value="jours" {form_field checked="jours" name=periodicite data=$transaction} /> <label for="f_periodicite_jours">En nombre de jours</label>
                <dl>
                    <dt class="periode_jours"><label for="f_duree">Durée de validité</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
                    <dd class="periode_jours"><input type="number" step="1" size="5" min="1" name="duree" id="f_duree" value="{form_field name="duree" data=$transaction}" /></dd>
                </dl>
            </dd>
            <dd><input type="radio" name="periodicite" id="f_periodicite_dates" value="date" {form_field checked="date" name=periodicite data=$transaction} /> <label for="f_periodicite_dates">Période définie</label>
                <dl>
                    <dt class="periode_dates"><label for="f_date_debut">Date de début</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
                    <dd class="periode_dates"><input type="date" name="debut" value="{form_field name=debut data=$transaction}" id="f_date_debut" /></dd>
                    <dt class="periode_dates"><label for="f_date_fin">Date de fin</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
                    <dd class="periode_dates"><input type="date" name="fin" value="{form_field name=fin data=$transaction}" id="f_date_fin" /></dd>
                </dl>
            </dd>
            <dt>
                <input type="checkbox" name="categorie" id="f_categorie" value="1" {form_field name="categorie" checked=1 data=$transaction} /> <label for="f_categorie">Enregistrer les transactions dans la comptabilité</label>
            </dt>
            <dt class="cat_compta"><label for="f_id_categorie_compta">Catégorie comptable</label></dt>
            <dd class="cat_compta">
                <select name="id_categorie_compta" id="f_id_categorie_compta">
                {foreach from=$categories item="cat"}
                    <option value="{$cat.id|escape}" {form_field name="id_categorie_compta" selected=$cat.id data=$transaction}>{$cat.intitule|escape}
                    {if !empty($cat.description)}
                        — <em>{$cat.description|escape}</em>
                    {/if}
                    </option>
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="edit_tr_`$transaction.id`"}
        <input type="submit" name="save" value="Ajouter &rarr;" />
    </p>

</form>

<script type="text/javascript">
{literal}
(function () {
    var hide = [];

    if (!$('#f_categorie').checked)
        hide.push('.cat_compta');

    if (!$('#f_periodicite_jours').checked)
        hide.push('.periode_jours');

    if (!$('#f_periodicite_dates').checked)
        hide.push('.periode_dates');

    toggleElementVisibility(hide, false);

    $('#f_categorie').onchange = function() {
        toggleElementVisibility('.cat_compta', this.checked);
        return true;
    };

    function togglePeriode()
    {
        toggleElementVisibility(['.periode_jours', '.periode_dates'], false);

        if (this.checked && this.value == 'jours')
            toggleElementVisibility('.periode_jours', true);
        else if (this.checked && this.value == 'date')
            toggleElementVisibility('.periode_dates', true);
    }

    $('#f_periodicite_ponctuel').onchange = togglePeriode;
    $('#f_periodicite_dates').onchange = togglePeriode;
    $('#f_periodicite_jours').onchange = togglePeriode;
})();
{/literal}
</script>

{include file="admin/_foot.tpl"}