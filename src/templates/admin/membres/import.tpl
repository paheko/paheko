{include file="admin/_head.tpl" title="Import & export des membres" current="membres" js=1}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{elseif $ok}
    <p class="confirm">
        L'import s'est bien déroulé.
    </p>
{/if}

<ul class="actions">
    <li class="current"><a href="{$www_url}admin/membres/import.php">Importer</a></li>
    <li><a href="{$www_url}admin/membres/import.php?export">Exporter en CSV</a></li>
</ul>

<form method="post" action="{$self_url|escape}" enctype="multipart/form-data">

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
                Export de la liste des membres au format CSV provenant de Garradin.
                Les lignes comportant un numéro de membre mettront à jour les fiches des membres ayant ce numéro,
                les lignes sans numéro créeront de nouveaux membres.
            </dd>
            <dd>
                <input type="radio" name="type" id="f_type_galette" value="galette" {form_field name=type checked="galette"} />
                <label for="f_type_galette">Export CSV de Galette</label>
            </dd>
            <dd class="help">
                Export des données au format CSV provenant du logiciel libre
                <a href="http://galette.eu/">Galette</a>.
            </dd>
            <dt class="galette"><label>Correspondance des champs</label></dt>
            <dd class="help">Indiquer quels champs des fiches membre de Garradin les données de Galette doivent remplir.</dd>
            <dd class="galette">
                <table class="list auto">
                    <tbody>
                    {foreach from=$galette_champs item="galette"}
                        {if is_int($galette)}{continue}{/if}
                        <tr>
                            <th>{$galette|escape}</th>
                            <td><select name="galette_translate[{$galette|escape}]">
                                <option value="">-- Ne pas importer ce champ</option>
                                {foreach from=$garradin_champs item="champ" key="name"}
                                {if $champ.type == 'checkbox' || $champ.type == 'multiple'}{continue}{/if}
                                <option value="{$name|escape}" {if (!empty($translate[$galette]) && $translate[$galette] == $name)}selected="selected"{/if}>{$champ.title|escape}</option>
                                {/foreach}
                            </select></td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="membres_import"}
        <input type="submit" name="import" value="Importer &rarr;" />
    </p>

</form>

<script type="text/javascript">
{literal}
(function () {
    function toggleGalette() {
        g.toggle('.galette', $('#f_type_galette').checked);
    }

    $('#f_type').onchange = toggleGalette;
    $('#f_type_galette').onchange = toggleGalette;
    toggleGalette();
})();
{/literal}
</script>

{include file="admin/_foot.tpl"}