{include file="admin/_head.tpl" title="Transactions" current="membres/transactions"}

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
                <td class="num">{$tr.montant|escape_money} {$config.monnaie|escape}</td>
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
                    <a href="{$admin_url}membres/transactions/modifier.php?id={$cat.id|escape}">Modifier</a>
                    | <a href="{$admin_url}membres/transactions/supprimer.php?id={$cat.id|escape}">Supprimer</a>
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

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Ajouter une transaction</legend>
        <dl>
            <dt><label for="f_intitule">Intitulé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="intitule" id="f_intitule" value="{form_field name=intitule}" /></dd>
            <dt><label for="f_description">Description</label></dt>
            <dd><textarea name="description" id="f_description" cols="50" rows="3">{form_field name=description}</textarea></dd>
            <dt><label for="f_montant">Montant</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" name="montant" step="0.01" min="0.00" id="f_montant" value="{form_field default=20 name=montant_cotisation default=0.00}" /></dd>
            <dt><label for="f_periodicite_jours">Validité de la transaction</label></dt>
            <dd><input type="radio" name="periodicite" id="f_periodicite_jours" value="jours" {form_field checked="jours" name=periodicite} /> <label for="f_periodicite_jours">En jours</label></dd>
            <dd><input type="radio" name="periodicite" id="f_periodicite_date" value="date" {form_field checked="date" name=periodicite} /> <label for="f_periodicite_date">De date à date</label></dd>
            <dd><input type="radio" name="periodicite" id="f_periodicite_ponctuel" value="ponctuel" {form_field checked="ponctuel" name=periodicite} /> <label for="f_periodicite_ponctuel">Ponctuelle</label></dd>
            <dt>
                <input type="checkbox" name="categorie" id="f_categorie" value="1" {form_field name="categorie" checked=1} /> <label for="f_categorie">Enregistrer les transactions dans la comptabilité</label>
            </dt>
            <dt class="cat_compta"><label for="f_id_categorie_compta">
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="new_transaction"}
        <input type="submit" name="save" value="Ajouter &rarr;" />
    </p>

</form>


{include file="admin/_foot.tpl"}