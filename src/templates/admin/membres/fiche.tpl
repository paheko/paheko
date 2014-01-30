{include file="admin/_head.tpl" title="`$membre.nom` (`$categorie.nom`)" current="membres"}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}">Membre n°{$membre.id|escape}</a></li>
    <li><a href="{$admin_url}membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
    {/if}
    <li><a href="{$admin_url}membres/transactions/membre.php?id={$membre.id|escape}">Suivi des paiements</a></li>
    <li><a href="{$admin_url}membres/transactions/ajout.php?id={$membre.id|escape}">Enregistrer un paiement</a></li>
</ul>

<dl class="cotisation">
{if $cotisation}
    <dt>Cotisation obligatoire</dt>
    <dd>{$cotisation.intitule|escape} — 
        {if $cotisation.duree}
            {$cotisation.duree|escape} jours
        {elseif $cotisation.debut}
            du {$cotisation.debut|format_sqlite_date_to_french} au {$cotisation.fin|format_sqlite_date_to_french}
        {else}
            ponctuelle
        {/if}
        — {$cotisation.montant|escape_money} {$config.monnaie|escape}
    </dd>
    <dt>À jour de cotisation ?</dt>
    <dd>
        {if !$statut_cotisation}
            <span class="error"><b>Non</b>, cotisation non payée ou expirée</span>
        {elseif $statut_cotisation == -1}
            <span class="alert"><b>Non</b>, cotisation payée partiellement</span>
        {else}
            <span class="confirm"><b>Oui</b>, cotisation à jour</span>
        {/if}
    </dd>
{/if}
    <dt>Paiements</dt>
    <dd>
        {if $nb_paiements == 1}
            {$nb_paiements|escape} paiement enregistré
        {elseif $nb_paiements}
            {$nb_paiements|escape} paiements enregistrés
        {else}
            Aucun paiement enregistré
        {/if} 
        — <a href="{$admin_url}transactions/membre.php?id={$membre.id|escape}">Voir l'historique</a></dd>
    <dd><form method="get" action="{$admin_url}transactions/ajout.php"><input type="submit" value="Enregistrer un paiement &rarr;" /></form></dd>
</dl>

<dl class="describe">
    <dt>Numéro d'adhérent</dt>
    <dd>{$membre.id|escape}</dd>
    {foreach from=$champs key="c" item="config"}
    <dt>{$config.title|escape}</dt>
    <dd>
        {if $config.type == 'checkbox'}
            {if $membre[$c]}Oui{else}Non{/if}
        {elseif empty($membre[$c])}
            <em>(Non renseigné)</em>
        {elseif $c == 'nom'}
            <strong>{$membre[$c]|escape}</strong>
        {elseif $c == 'email'}
            <a href="mailto:{$membre[$c]|escape}">{$membre[$c]|escape}</a>
            | <a href="{$www_url}admin/membres/message.php?id={$membre.id|escape}">Envoyer un message</a>
        {elseif $config.type == 'email'}
            <a href="mailto:{$membre[$c]|escape}">{$membre[$c]|escape}</a>
        {elseif $config.type == 'tel'}
            <a href="tel:{$membre[$c]|escape}">{$membre[$c]|escape|format_tel}</a>
        {elseif $config.type == 'country'}
            {$membre[$c]|get_country_name|escape}
        {elseif $config.type == 'date' || $config.type == 'datetime'}
            {$membre[$c]|format_sqlite_date_to_french}
        {elseif $c == 'passe'}
            Oui
        {elseif $config.type == 'password'}
            *******
        {elseif $config.type == 'multiple'}
            <ul>
            {foreach from=$config.options key="b" item="name"}
                {if $membre[$c] & (0x01 << $b)}
                    <li>{$name|escape}</li>
                {/if}
            {/foreach}
            </ul>
        {else}
            {$membre[$c]|escape|rtrim|nl2br}
        {/if}
    </dd>
    {/foreach}
    <dt>Catégorie</dt>
    <dd>{$categorie.nom|escape} <span class="droits">{format_droits droits=$categorie}</span></dd>
    <dt>Inscription</dt>
    <dd>{$membre.date_inscription|date_fr:'d/m/Y'}</dd>
    <dt>Dernière connexion</dt>
    <dd>{if empty($membre.date_connexion)}Jamais{else}{$membre.date_connexion|date_fr:'d/m/Y à H:i'}{/if}</dd>
</dl>

{include file="admin/_foot.tpl"}