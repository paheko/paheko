{include file="admin/_head.tpl" title="Modifier une cotisation" current="membres/cotisations" js=1}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
    <li><a href="{$admin_url}membres/cotisations/ajout.php">Saisie d'une cotisation</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$admin_url}membres/cotisations/gestion/rappels.php">Gestion des rappels automatiques</a></li>
    {/if}
</ul>

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Modifier une cotisation</legend>
        <dl>
            <dt><label for="f_intitule">Intitulé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="intitule" id="f_intitule" value="{form_field name=intitule data=$cotisation}" required="required" /></dd>
            <dt><label for="f_description">Description</label></dt>
            <dd><textarea name="description" id="f_description" cols="50" rows="3">{form_field name=description data=$cotisation}</textarea></dd>
            <dt><label for="f_montant">Montant</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" name="montant" step="0.01" min="0.00" id="f_montant" value="{form_field default=20 name=montant default=0.00 data=$cotisation}" required="required" /></dd>

            <dt><label for="f_periodicite_jours">Période de validité</label></dt>
            <dd><input type="radio" name="periodicite" id="f_periodicite_ponctuel" value="ponctuel" {form_field checked="ponctuel" name=periodicite default="ponctuel" data=$cotisation} /> <label for="f_periodicite_ponctuel">Pas de période (activité ou cotisation ponctuelle)</label></dd>

            <dd><input type="radio" name="periodicite" id="f_periodicite_jours" value="jours" {form_field checked="jours" name=periodicite data=$cotisation} /> <label for="f_periodicite_jours">En nombre de jours</label>
                <dl class="periode_jours">
                    <dt><label for="f_duree">Durée de validité</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
                    <dd><input type="number" step="1" size="5" min="1" name="duree" id="f_duree" value="{form_field name="duree" data=$cotisation}" /></dd>
                </dl>
            </dd>
            <dd><input type="radio" name="periodicite" id="f_periodicite_dates" value="date" {form_field checked="date" name=periodicite data=$cotisation} /> <label for="f_periodicite_dates">Période définie</label>
                <dl class="periode_dates">
                    <dt><label for="f_date_debut">Date de début</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
                    <dd><input type="date" name="debut" value="{form_field name=debut data=$cotisation}" id="f_date_debut" /></dd>
                    <dt><label for="f_date_fin">Date de fin</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
                    <dd><input type="date" name="fin" value="{form_field name=fin data=$cotisation}" id="f_date_fin" /></dd>
                </dl>
            </dd>
            <dt>
                <input type="checkbox" name="categorie" id="f_categorie" value="1" {form_field name="categorie" checked=1 data=$cotisation} /> <label for="f_categorie">Enregistrer les cotisations des membres dans la comptabilité</label>
            </dt>
            <dd class="help cat_compta">
                Si coché, à chaque enregistrement de cotisation d'un membre une opération 
                du montant de la cotisation sera enregistrée dans la comptabilité selon
                la catégorie choisie.
            </dd>
            <dt class="cat_compta"><label for="f_id_categorie_compta">Catégorie comptable</label></dt>
            <dd class="cat_compta">
                <select name="id_categorie_compta" id="f_id_categorie_compta">
                {foreach from=$categories item="cat"}
                    <option value="{$cat.id|escape}" {form_field name="id_categorie_compta" selected=$cat.id data=$cotisation}>{$cat.intitule|escape}
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
        {csrf_field key="edit_co_`$cotisation.id`"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
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

    g.toggle(hide, false);

    $('#f_categorie').onchange = function() {
        g.toggle('.cat_compta', this.checked);
        return true;
    };

    function togglePeriode()
    {
        g.toggle(['.periode_jours', '.periode_dates'], false);

        if (this.checked && this.value == 'jours')
            g.toggle('.periode_jours', true);
        else if (this.checked && this.value == 'date')
            g.toggle('.periode_dates', true);
    }

    $('#f_periodicite_ponctuel').onchange = togglePeriode;
    $('#f_periodicite_dates').onchange = togglePeriode;
    $('#f_periodicite_jours').onchange = togglePeriode;
})();
{/literal}
</script>

{include file="admin/_foot.tpl"}