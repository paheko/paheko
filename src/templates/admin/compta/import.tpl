{include file="admin/_head.tpl" title="Import / Export" current="compta"}

{form_errors}

{if $ok}
    <p class="confirm">
        L'import s'est bien déroulé.
    </p>
{/if}

<ul class="actions">
    <li class="current"><a href="{$www_url}admin/compta/import.php">Importer</a></li>
    <li><a href="{$www_url}admin/compta/import.php?export">Exporter en CSV</a></li>
</ul>

<form method="post" action="{$self_url}" enctype="multipart/form-data">

    <fieldset>
        <legend>Importer depuis un fichier</legend>
        <dl>
            <dt><label for="f_file">Fichier à importer</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="file" name="upload" id="f_file" required="required" /></dd>
            <dt><label for="f_type">Type de fichier</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <input type="radio" name="type" id="f_type" value="garradin" {form_field name=type checked="garradin" default="garradin"} />
                <label for="f_type">Export CSV de Garradin</label>
            </dd>
            <dd class="help">
                Export du journal comptable au format CSV provenant de Garradin.
                Les lignes comportant un numéro d'opération mettront à jour les opérations existantes,
                les lignes sans numéro créeront de nouvelles opérations.
            </dd>
            <dd>
                <input type="radio" name="type" id="f_type_citizen" value="citizen" {form_field name=type checked="citizen"} />
                <label for="f_type_citizen">Export CSV de Citizen Comptabilité</label>
            </dd>
            <dd class="help">
                Export des données au format CSV provenant du logiciel de comptabilité de
                <a href="http://www.citizenplace.com/">Citizen Place</a>.
            </dd>
            <dd class="help">
                Toutes les opérations du fichier seront créées dans l'exercice en cours.  Les catégories et comptes associés aux opérations seront automatiquement créés s'ils n'existent pas déjà.
            </dd>
        </dl>
    </fieldset>

    <p class="alert">
        Si le fichier comporte des opérations dont la date est en dehors de l'exercice courant,
        elles seront ignorées.
    </p>

    <p class="submit">
        {csrf_field key="compta_import"}
        <input type="submit" name="import" value="Importer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}