{include file="admin/_head.tpl" title="Configuration — Site public" current="config" js=1}

{form_errors}

{include file="admin/config/_menu.tpl" current="site"}

{if isset($edit)}
    <form method="post" action="{$self_url}">
        <h3>Éditer un squelette</h3>

        {if $ok}
        <p class="confirm">
            Modifications enregistrées.
        </p>
        {/if}

        <fieldset class="skelEdit">
            <legend>{$edit.file}</legend>
            <p>
                <textarea name="content" cols="90" rows="50" id="f_content">{form_field name=content data=$edit}</textarea>
            </p>
        </fieldset>

        <p class="submit">
            {csrf_field key=$csrf_key}
            <input type="submit" name="save" value="Enregistrer &rarr;" />
        </p>

    </form>

    <script type="text/javascript">
    var doc_url = "{$admin_url}doc/skel/";
    var skel_list = {$sources|escape:json};
    var skel_current = "{$edit.file|escape:'js'}";
    </script>
    <script type="text/javascript" src="{$admin_url}static/scripts/skel_editor.js"></script>
{else}

    <fieldset>
        <legend>Gérer le site public</legend>
        <p><a href="{$admin_url}wiki/creer.php?public">Créer une nouvelle page sur le site web public</a></p>
    </fieldset>

    <form method="post" action="{$self_url}">
    <fieldset class="templatesList">
        <legend>Squelettes du site</legend>

        {if $reset_ok}
        <p class="confirm">
            Réinitialisation effectuée. Les squelettes ont été remis à jour
        </p>
        {/if}

        <table class="list">
            <thead>
                <tr>
                    <td class="check"></td>
                    <th>Fichier</th>
                    <td>Dernière modification</td>
                    <td></td>
                </tr>
            </thead>
            <tbody>
            {foreach from=$sources key="source" item="local"}
                <tr>
                    <td>{if $local && $local.dist}<input type="checkbox" name="select[]" value="{$source}" />{/if}</td>
                    <th><a href="{$admin_url}config/site.php?edit={$source|escape:'url'}" title="Éditer">{$source}</a></th>
                    <td>{if $local}{$local.mtime|date_fr:'d/m/Y à H:i:s'}{else}<em>(fichier non modifié)</em>{/if}</td>
                    <td class="actions">
                        <a class="icn" href="{$admin_url}config/site.php?edit={$source|escape:'url'}" title="Éditer">✎</a>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>

        <p class="actions">
            Pour les squelettes sélectionnés&nbsp;:
            <input type="submit" name="reset" value="Réinitialiser" onclick="return confirm('Effacer toute modification locale et restaurer les squelettes d\'installation ?');" />
            {csrf_field key="squelettes"}
        </p>
    </fieldset>
    </form>
{/if}

{include file="admin/_foot.tpl"}