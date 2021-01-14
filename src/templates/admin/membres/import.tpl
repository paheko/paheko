{include file="admin/_head.tpl" title="Import & export des membres" current="membres"}

{include file="admin/membres/_nav.tpl" current="import"}

<nav class="tabs">
    <ul class="sub">
        <li class="current"><a href="{$admin_url}membres/import.php">Importer</a></li>
        <li><a href="{$admin_url}membres/import.php?export=csv">Exporter en CSV</a></li>
        <li><a href="{$admin_url}membres/import.php?export=ods">Exporter en classeur Office</a></li>
    </ul>
</nav>

{form_errors}

{if $ok}
    <p class="block confirm">
        L'import s'est bien déroulé.
    </p>
{/if}

<form method="post" action="{$self_url}" enctype="multipart/form-data">

    {if $csv->loaded()}

        {include file="common/_csv_match_columns.tpl"}

    {else}

    <fieldset>
        <legend>Importer depuis un fichier</legend>
        <dl>
            <dt><label for="f_file">Fichier à importer</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="help">La taille maximale du fichier est de {$max_upload_size|format_bytes}.</dd>
            <dd><input type="file" name="upload" id="f_file" required="required" /></dd>
            <dt><label for="f_type">Type de fichier</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <input type="radio" name="type" id="f_type" value="garradin" {form_field name=type checked="garradin" default="garradin"} />
                <label for="f_type">Fichier CSV de Garradin</label>
            </dd>
            <dd class="help">
                Export de la liste des membres au format CSV provenant de Garradin.
                Les lignes comportant un numéro de membre mettront à jour les fiches des membres ayant ce numéro (si le numéro existe),
                les lignes sans numéro ou avec un numéro inexistant créeront de nouveaux membres.
            </dd>
            <dd>
                <input type="radio" name="type" id="f_type_csv" value="custom" {form_field name=type checked="csv"} />
                <label for="f_type_csv">Fichier CSV générique</label>
            </dd>
            <dd class="help">
                Vous pourrez choisir la correspondance entre colonnes du CSV et champs des fiches membres
                dans le prochain écran.
            </dd>
        </dl>
    </fieldset>

    {/if}

    <p class="submit">
        {csrf_field key=$csrf_key}
        {if $csv->loaded()}{button type="submit" name="cancel" value="1" label="Annuler" shape="left"}{/if}
        {button type="submit" name="import" label="Importer" shape="upload" class="main"}
    </p>

</form>

{include file="admin/_foot.tpl"}