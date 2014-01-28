{include file="admin/_head.tpl" title="Cotisations et activités" current="membres/transactions/admin" js=1}

<table class="list">
    <thead>
        <th>Intitulé</th>
        <td>Montant</td>
        <td>Période</td>
        <td></td>
    </thead>
    <tbody>
        {foreach from=$liste item="tr"}
            <tr>
                <th>{$tr.intitule|escape}</th>
                <td class="num">{$tr.montant|html_money} {$config.monnaie|escape}</td>
                <td>
                    {if $tr.duree}
                        {$tr.duree|escape} jours
                    {elseif $tr.debut}
                        du {$tr.debut|format_sqlite_date_to_french} au {$tr.fin|format_sqlite_date_to_french}
                    {else}
                        ponctuelle
                    {/if}
                </td>
                <td class="actions">
                    <a href="{$admin_url}membres/transactions/gestion/modifier.php?id={$tr.id|escape}">Modifier</a>
                    | <a href="{$admin_url}membres/transactions/gestion/supprimer.php?id={$tr.id|escape}">Supprimer</a>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}" id="f_add">

    <fieldset>
        <legend>Ajouter une activité ou cotisation</legend>
        <dl>
            <dt><label for="f_intitule">Intitulé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="intitule" id="f_intitule" value="{form_field name=intitule}" /></dd>
            <dt><label for="f_description">Description</label></dt>
            <dd><textarea name="description" id="f_description" cols="50" rows="3">{form_field name=description}</textarea></dd>
            <dt><label for="f_montant">Montant</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" name="montant" step="0.01" min="0.00" id="f_montant" value="{form_field default=20 name=montant_cotisation default=0.00}" /></dd>

            <dt><label for="f_periodicite_jours">Période de validité</label></dt>
            <dd><input type="radio" name="periodicite" id="f_periodicite_ponctuel" value="ponctuel" {form_field checked="ponctuel" name=periodicite default="ponctuel"} /> <label for="f_periodicite_ponctuel">Pas de période (activité ou cotisation ponctuelle)</label></dd>

            <dd><input type="radio" name="periodicite" id="f_periodicite_jours" value="jours" {form_field checked="jours" name=periodicite} /> <label for="f_periodicite_jours">En nombre de jours</label></dd>
            <dt class="periode_jours"><label for="f_duree">Durée de validité</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="periode_jours"><input type="number" step="1" size="5" min="1" name="duree" id="f_duree" value="{form_field name="duree"}" /></dd>

            <dd><input type="radio" name="periodicite" id="f_periodicite_date" value="date" {form_field checked="date" name=periodicite} /> <label for="f_periodicite_date">Période définie</label></dd>

            <dt class="periode_dates"><label for="f_date_debut">Date de début</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="periode_dates"><input type="date" name="debut" value="{form_field name=debut}" id="f_date_debut" /></dd>
            <dt class="periode_dates"><label for="f_date_fin">Date de fin</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="periode_dates"><input type="date" name="fin" value="{form_field name=fin}" id="f_date_fin" /></dd>
            <dt>
                <input type="checkbox" name="categorie" id="f_categorie" value="1" {form_field name="categorie" checked=1} /> <label for="f_categorie">Enregistrer les transactions dans la comptabilité</label>
            </dt>
            <dt class="cat_compta"><label for="f_id_categorie_compta">Catégorie comptable</label></dt>
            <dd class="cat_compta">
                <select name="id_categorie_compta" id="f_id_categorie_compta">
                {foreach from=$categories item="cat"}
                    <option value="{$cat.id|escape}" {form_field name="id_categorie_compta" selected=$cat.id}>{$cat.intitule|escape}
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
        {csrf_field key="new_transaction"}
        <input type="submit" name="save" value="Ajouter &rarr;" />
    </p>

</form>

<script type="text/javascript">
{literal}
(function () {
    toggleElementVisibility(['.cat_compta', '.periode_jours', '.periode_dates'], false);

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
    $('#f_periodicite_date').onchange = togglePeriode;
    $('#f_periodicite_jours').onchange = togglePeriode;
})();
{/literal}
</script>

{include file="admin/_foot.tpl"}