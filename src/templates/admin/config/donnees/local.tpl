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
    <legend>Copies de sauvegarde disponibles</legend>
    {if empty($liste)}
        <p class="help">Aucune copie de sauvegarde disponible.</p>
    {else}
        <dl>
            <dt><label for="f_select">Sélectionner une sauvegarde</label></dt>
            <dd>
                <select name="file" id="f_select">
                {foreach from=$liste key="f" item="d"}
                    <option value="{$f}">{$f} — {$d|date_long}</option>
                {/foreach}
                </select>
            </dd>
            <dd class="help">
                Attention, en cas de restauration, l'intégralité des données courantes seront effacées et remplacées par celles contenues dans la sauvegarde sélectionnée. Cependant, afin de prévenir toute erreur
                une sauvegarde des données sera réalisée avant la restauration.
            </dd>
        </dl>
        <p>
            {csrf_field key="backup_manage"}
            {button type="submit" name="restore" label="Restaurer cette sauvegarde" shape="reset"}
            {button type="submit" name="delete" label="Supprimer cette sauvegarde" shape="delete"}
        </p>
    {/if}
</fieldset>

</form>

<form method="post" action="{$self_url_no_qs}">

<fieldset>
    <legend>Sauvegarde manuelle</legend>
    <p class="submit">
        {csrf_field key="backup_create"}
        {button type="submit" name="create" label="Créer une nouvelle sauvegarde des données" shape="right" class="main"}
    </p>
</fieldset>

</form>

{include file="admin/_foot.tpl"}