{include file="admin/_head.tpl" title="Import & export des membres" current="membres" js=1}

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

    {if $csv_file}

    <fieldset>
        <legend>Importer depuis un fichier CSV générique</legend>
        <p class="help">{$csv_file|count} lignes trouvées dans le fichier</p>
        <dl>
            <dt><label><input type="checkbox" name="skip_first_line" value="1" checked="checked" /> Ne pas importer la première ligne</label></dt>
            <dd class="help">Décocher cette case si la première ligne ne contient pas l'intitulé des colonnes mais des données.</dd>
            <dt><label>Correspondance des champs</label></dt>
            <dd class="help">Indiquer la correspondance entre colonnes du CSV et champs des fiches membre.</dd>
            <dd>
                <table class="list auto">
                    <tbody>
                    {foreach from=$csv_first_line key="index" item="csv_field"}
                        <tr>
                            <th>{$csv_field}</th>
                            <td>
                                <select name="csv_translate[{$index}]">
                                    <option value="">-- Ne pas importer ce champ</option>
                                    {foreach from=$garradin_champs item="champ" key="name"}
                                        {if $champ.type == 'multiple' || $champ.type == 'file' || $name == 'passe'}{continue}{/if}
                                        <option value="{$name}">{$champ.title}</option>
                                    {/foreach}
                                </select>
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </dd>
            <dd class="help">Pour fusionner des colonnes, il suffit d'indiquer le même nom de champ pour plusieurs colonnes.</dd>
        </dl>
    </fieldset>

    <input type="hidden" name="csv_encoded" value="{$csv_file|escape:'json'|escape}" />

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
                <input type="radio" name="type" id="f_type_csv" value="csv" {form_field name=type checked="csv"} />
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
        {csrf_field key="membres_import"}
        <input type="submit" name="import" value="Importer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}