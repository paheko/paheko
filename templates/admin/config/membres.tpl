{include file="admin/_head.tpl" title="Configuration" current="config"}

{if $error}
    {if $error == 'OK'}
    <p class="confirm">
        La configuration a bien été enregistrée.
    </p>
    {else}
    <p class="error">
        {$error|escape}
    </p>
    {/if}
{/if}

<ul class="actions">
    <li><a href="{$www_url}admin/config/">Général</a></li>
    <li class="current"><a href="{$www_url}admin/config/membres.php">Membres</a></li>
    <li><a href="{$www_url}admin/config/site.php">Site public</a></li>
</ul>

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Champs des données membres</legend>
        <dl>
            <dt><label for="f_champs_obligatoires_nom">Champs obligatoires</label></dt>
            <dd>
            {foreach from=$champs_membres key="champ" item="nom"}
                <input type="checkbox" name="champs_obligatoires[]"
                    id="f_champs_obligatoires_{$champ|escape}"
                    value="{$champ|escape}"
                    {if $champ == 'nom'}checked="checked" disabled="disabled"
                    {elseif in_array($champ, $config.champs_obligatoires)}checked="checked"
                    {/if}
                    />
                <label for="f_champs_obligatoires_{$champ|escape}">{$nom|escape}</label>
                {if $champ == 'nom'}<small>(non désactivable)</small>{/if}
                <br />
            {/foreach}
            </dd>
            <dt><label for="f_champs_modifiables_membre_nom">Champs modifiables par le membre</label></dt>
            <dd>
            {foreach from=$champs_membres key="champ" item="nom"}
                <input type="checkbox" name="champs_modifiables_membre[]"
                    id="f_champs_modifiables_membre_{$champ|escape}"
                    value="{$champ|escape}"
                    {if in_array($champ, $config.champs_modifiables_membre)}checked="checked"
                    {/if}
                    />
                <label for="f_champs_modifiables_membre_{$champ|escape}">{$nom|escape}</label><br />
            {/foreach}
            </dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Catégories par défaut</legend>
        <dl>
            <dt><label for="f_categorie_membres">Catégorie par défaut des nouveaux membres</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="categorie_membres" id="f_categorie_membres">
                {foreach from=$membres_cats key="id" item="nom"}
                    <option value="{$id|escape}"{if $config.categorie_membres == $id} selected="selected"{/if}>{$nom|escape}</option>
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="config_membres"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}