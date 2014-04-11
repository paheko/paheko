{include file="admin/_head.tpl" title="Extensions" current="config"}

{include file="admin/config/_menu.tpl" current="plugins"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

{if !empty($delete)}
    <form method="post" action="{$self_url|escape}">

        <fieldset>
            <legend>Désinstaller une extension</legend>
            <h3 class="warning">
                Êtes-vous sûr de vouloir supprimer l'extension «&nbsp;{$plugin.nom|escape}&nbsp;» ?
            </h3>
            <p class="alert">
                <strong>Attention</strong> : cette action est irréversible et effacera toutes les
                données associées à l'extension.
            </p>
        </fieldset>

        <p class="submit">
            {csrf_field key="delete_plugin_`$plugin.id`"}
            <input type="submit" name="delete" value="Désinstaller &rarr;" />
        </p>
    </form>
{else}
    {if !empty($liste_installes)}
        <table class="list">
            <thead>
                <tr>
                    <th>Extension</th>
                    <td>Auteur</td>
                    <td>Version installée</td>
                    <td></td>
                </tr>
            </thead>
            <tbody>
                {foreach from=$liste_installes item="plugin"}
                <tr>
                    <th>
                        <h4>{$plugin.nom|escape}</h4>
                        <small>{$plugin.description|escape}</small>
                    </th>
                    <td>
                        <a href="{$plugin.url|escape}" onclick="return !window.open(this.href);">{$plugin.auteur|escape}</a>
                    </td>
                    <td>
                        {$plugin.version|escape}
                    </td>
                    <td class="actions">
                        {if empty($plugin.system)}
                            <a href="{$admin_url}config/plugins.php?delete={$plugin.id|escape}">Désinstaller</a>
                        {/if}
                        {if !empty($plugin.config)}
                            {if empty($plugin.system)}|{/if}
                            <a href="{plugin_url id=$plugin.id file="config.php"}">Configurer</a>
                        {/if}
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <p class="help">
            Aucune extension n'est installée.
            Vous pouvez consulter <a href="{Garradin\WEBSITE}">le site de Garradin</a> pour obtenir
            des extensions à télécharger.
        </p>
    {/if}

    {if !empty($liste_telecharges)}
    <form method="post" action="{$self_url|escape}">

        <fieldset>
            <legend>Extensions à installer</legend>
            <dl>
                {foreach from=$liste_telecharges item="plugin" key="id"}
                <dt>
                    <label>
                        <input type="radio" name="to_install" value="{$id|escape}" />
                        {$plugin.nom|escape}
                    </label>
                    (version {$plugin.version|escape})
                </dt>
                <dd>[<a href="{$plugin.url|escape}" onclick="return !window.open(this.href);">{$plugin.auteur|escape}</a>] {$plugin.description|escape}</dd>
                {/foreach}
            </dl>
        </fieldset>

        <p class="help">
            Attention : installer une extension non officielle peut présenter des risques de sécurité
            et de stabilité.
        </p>

        <p class="submit">
            {csrf_field key="install_plugin"}
            <input type="submit" name="install" value="Installer &rarr;" />
        </p>
    </form>
    {/if}
{/if}

{include file="admin/_foot.tpl"}