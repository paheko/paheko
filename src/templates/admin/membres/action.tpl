{include file="admin/_head.tpl" title="Action collective sur les membres" current="membres"}

{form_errors}

<form method="post" action="{$self_url}">
    {foreach from=$selected item="id"}
        <input type="hidden" name="selected[]" value="{$id}" />
    {/foreach}

    </fieldset>

    {if $action == 'move'}
    <fieldset>
        <legend>Changer la catégorie des {$nb_selected} membres sélectionnés</legend>
        <dl>
            <dt><label for="f_cat">Nouvelle catégorie</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="id_categorie" id="f_cat">
                    <option value="0" selected="selected">-- Pas de changement</option>
                {foreach from=$membres_cats key="id" item="nom"}
                    <option value="{$id}">{$nom}</option>
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="membres_action"}
        <input type="hidden" name="action" value="move" />
        <input type="submit" name="confirm" value="Enregistrer &rarr;" />
    </p>

    {elseif $action == 'delete'}
    <fieldset>
        <legend>Supprimer les membres sélectionnés ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer les {$nb_selected} membres sélectionnés ?
        </h3>
        <p class="alert">
            <strong>Attention</strong> : cette action est irréversible et effacera toutes les
            données personnelles et l'historique de ces membres.
        </p>
        <p class="help">
            Alternativement, il est aussi possible de déplacer les membres qui ne font plus
            partie de l'association dans une catégorie «&nbsp;Anciens membres&nbsp;», plutôt
            que de les effacer complètement.
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="membres_action"}
        <input type="hidden" name="action" value="delete" />
        <input type="submit" name="confirm" value="Oui, supprimer ces membres &rarr;" />
    </p>
    {/if}

</form>

{include file="admin/_foot.tpl"}