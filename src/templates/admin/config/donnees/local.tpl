{include file="admin/_head.tpl" title="Gestion des sauvegardes" current="config"}

{include file="admin/config/_menu.tpl" current="donnees"}

{include file="admin/config/donnees/_menu.tpl" current="local"}

{form_errors}

{if $ok}
    <p class="block confirm">
        {if $ok == 'create'}Une nouvelle sauvegarde a été créée.
        {elseif $ok == 'restore'}La restauration a bien été effectuée. Si vous désirez revenir en arrière, vous pouvez utiliser la sauvegarde automatique nommée <em>date-du-jour.avant_restauration.sqlite</em>, sinon vous pouvez l'effacer.
            {if $ok_code & Sauvegarde::NOT_AN_ADMIN}
            </p>
            <p class="block alert">
                <strong>Vous n'êtes pas administrateur dans cette sauvegarde.</strong> Garradin a donné les droits d'administration à toutes les catégories afin d'empêcher de ne plus pouvoir se connecter.
                Merci de corriger les droits des catégories maintenant.
            {/if}
        {elseif $ok == 'remove'}La sauvegarde a été supprimée.
        {/if}
    </p>
{/if}


<form method="post" action="{$self_url_no_qs}">

<fieldset>
    <legend>Sauvegarde manuelle</legend>
    <p class="submit">
        {csrf_field key="backup_create"}
        {button type="submit" name="create" label="Créer une nouvelle sauvegarde manuelle" shape="right" class="main"}
    </p>
</fieldset>

</form>

<form method="post" action="{$self_url_no_qs}">

<fieldset>
    <legend>Sauvegardes disponibles</legend>
    {if empty($list)}
        <p class="help">Aucune copie de sauvegarde disponible.</p>
    {else}
        <table class="list">
            <tbody>
            {foreach from=$list item="backup"}
                <tr>
                    <td class="check">{if $backup.can_restore}{input type="radio" name="selected" value=$backup.filename}{/if}</td>
                    <th><label for="f_selected_{$backup.filename}">{$backup.name}</label></th>
                    <td>{$backup.size|format_bytes}</td>
                    <td>{$backup.date|date_long}</td>
                    <td>{if !$backup.can_restore}<span class="alert">Version {$backup.version} trop ancienne pour pouvoir être restaurée</span>{/if}</td>
                    <td class="actions">
                        {linkbutton href="?download=%s"|args:$backup.filename label="Télécharger" shape="download"}
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
        <p class="alert block">
            Attention, en cas de restauration, l'intégralité des données courantes seront effacées et remplacées par celles contenues dans la sauvegarde sélectionnée.
        </p>
        <p>
            {csrf_field key="backup_manage"}
            {button type="submit" name="restore" label="Restaurer cette sauvegarde" shape="reset"}
            {button type="submit" name="remove" label="Supprimer cette sauvegarde" shape="delete"}
        </p>
    {/if}
</fieldset>

</form>

{include file="admin/_foot.tpl"}