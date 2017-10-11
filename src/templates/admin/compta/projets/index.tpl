{include file="admin/_head.tpl" title="Projets" current="compta/exercices"}

<ul class="actions">
    <li><a href="{$admin_url}compta/exercices/">Exercices</a></li>
    <li class="current"><a href="{$admin_url}compta/projets/">Projets (compta analytique)</a></li>
</ul>

{form_errors}

{if $action == 'modifier'}

    <form method="post" action="{$self_url}">
        <fieldset>
            <legend>Modifier un projet</legend>
            <dl>
                <dt><label for="f_libelle">Libellé</label></dt>
                <dd><input type="text" name="libelle" id="f_labelle" value="{form_field name=libelle data=$projet}" /></dd>
            </dl>
            <p class="submit">
                {csrf_field key="modifier_projet_%d"|args:$projet.id}
                <input type="submit" name="modifier" value="Modifier &rarr;" />
            </p>
        </fieldset>
    </form>
{elseif $action == 'supprimer'}

    <form method="post" action="{$self_url}">

        <fieldset>
            <legend>Supprimer le projet ?</legend>
            <h3 class="warning">
                Êtes-vous sûr de vouloir supprimer le projet «&nbsp;{$projet.libelle}&nbsp;» ?
            </h3>
            <p class="help">
                Les opérations liées à ce projet ne seront pas supprimées, mais n'auront
                plus de projet lié.
            </p>
        </fieldset>

        <p class="submit">
            {csrf_field key="supprimer_projet_%d"|args:$projet.id}
            <input type="submit" name="supprimer" value="Supprimer &rarr;" />
        </p>

    </form>

{else}
    {if !empty($liste)}
        <dl class="catList">
        {foreach from=$liste item="projet"}
            <dt>{$projet.libelle}</dt>
            <dd class="compte">{$projet.nb_operations} opérations</dd>
            <dd class="desc">
                <a href="{$admin_url}compta/rapports/journal.php?projet={$projet.id}">Journal général</a>
                | <a href="{$admin_url}compta/rapports/grand_livre.php?projet={$projet.id}">Grand livre</a>
                | <a href="{$admin_url}compta/rapports/compte_resultat.php?projet={$projet.id}">Compte de résultat</a>
                | <a href="{$admin_url}compta/rapports/bilan.php?projet={$projet.id}">Bilan</a>
            </dd>
            {if $session->canAccess('compta', Garradin\Membres::DROIT_ADMIN)}
            <dd class="actions">
                <a class="icn" href="{$admin_url}compta/projets/?modifier={$projet.id}" title="Modifier">✎</a>
                <a class="icn" href="{$admin_url}compta/projets/?supprimer={$projet.id}" title="Supprimer">✘</a>
            </dd>
            {/if}
        {/foreach}
        </dl>
    {/if}

    {if $session->canAccess('compta', Garradin\Membres::DROIT_ADMIN)}
    <form method="post" action="{$self_url}">
        <fieldset>
            <legend>Ajouter un nouveau projet</legend>
            <dl>
                <dt><label for="f_libelle">Libellé</label></dt>
                <dd><input type="text" name="libelle" id="f_labelle" value="{form_field name=libelle}" /></dd>
            </dl>
            <p class="submit">
                {csrf_field key="ajout_projet"}
                <input type="submit" name="ajouter" value="Ajouter &rarr;" />
            </p>
        </fieldset>
    </form>
    {/if}
{/if}

{include file="admin/_foot.tpl"}