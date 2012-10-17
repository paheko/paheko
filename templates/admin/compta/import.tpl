{include file="admin/_head.tpl" title="Import / Export" current="compta"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<ul class="actions">
    <li><a href="{$www_url}admin/compta/import.php">Importer</a></li>
    <li><a href="{$www_url}admin/compta/import.php?export">Exporter en CSV</a></li>
</ul>

<form method="post" action="{$self_url|escape}" enctype="multipart/form-data">

    <fieldset>
        <legend>Importer depuis un fichier</legend>
        <dl>
            <dt><label for="f_file">Fichier à importer</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="file" name="upload" id="f_file" /></dd>
            <dt><label for="f_type">Type de fichier</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <input type="radio" name="type" id="f_type" value="citizen" {form_field name=type checked="citizen" default="citizen"} />
                <label for="f_type">Export CSV de Citizen Comptabilité</label>
            </dd>
            <dd class="help">
                Export des données au format CSV provenant du logiciel de comptabilité de
                <a href="http://www.citizenplace.com/">Citizen Place</a>.
            </dd>
        </dl>
    </fieldset>

    <p class="help">
        Attention l'import ne pourra pas s'effectuer si des opérations contenues dans le fichier
        ne concernent pas la période de l'exercice en cours. Les catégories et comptes associés
        aux opérations seront automatiquement créés s'ils n'existent pas déjà.
    </p>

    <p class="submit">
        {csrf_field key="compta_import"}
        <input type="submit" name="import" value="Importer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}