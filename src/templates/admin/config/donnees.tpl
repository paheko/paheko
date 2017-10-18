{include file="admin/_head.tpl" title="Données — Sauvegarde et restauration" current="config"}

{include file="admin/config/_menu.tpl" current="donnees"}

{form_errors}

{if $code == Garradin\Sauvegarde::INTEGRITY_FAIL && Garradin\ALLOW_MODIFIED_IMPORT}
    <p class="alert">Pour passer outre, renvoyez le fichier en cochant la case «&nbsp;Ignorer les erreurs&nbsp;».
    Attention, si vous avez effectué des modifications dans la base de données, cela peut créer des bugs&nbsp;!</p>
{/if}

{if $ok}
    <p class="confirm">
        {if $ok == 'config'}La configuration a bien été enregistrée.
        {elseif $ok == 'create'}Une nouvelle sauvegarde a été créée.
        {elseif $ok == 'restore'}La restauration a bien été effectuée. Si vous désirez revenir en arrière, vous pouvez utiliser la sauvegarde automatique nommée <em>date-du-jour.avant_restauration.sqlite</em>, sinon vous pouvez l'effacer.
            {if $ok_code & Garradin\Sauvegarde::NOT_AN_ADMIN}
            </p>
            <p class="alert">
                <strong>Vous n'êtes pas administrateur dans cette sauvegarde.</strong> Garradin a donné les droits d'administration à toutes les catégories afin d'empêcher de ne plus pouvoir se connecter.
                Merci de corriger les droits des catégories maintenant.
            {/if}
        {elseif $ok == 'remove'}La sauvegarde a été supprimée.
        {/if}
    </p>
{/if}

<form method="post" action="{$self_url_no_qs}">

<p class="help">
    Info : la base de données fait actuellement {$db_size|format_bytes} (dont {$files_size|format_bytes} pour les documents et images).
</p>

<fieldset>
    <legend>Sauvegarde automatique</legend>
    <p class="help">
        En activant cette option une sauvegarde sera automatiquement créée à chaque intervalle donné.
        Par exemple en activant une sauvegarde hebdomadaire, une copie des données sera réalisée
        une fois par semaine, sauf si aucune modification n'a été effectuée sur les données
        ou que personne ne s'est connecté.
    </p>
    <dl>
        <dt><label for="f_frequency">Intervalle de sauvegarde</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
        <dd>
            <select name="frequence_sauvegardes" required="required" id="f_frequency">
                <option value="0"{form_field name=frequence_sauvegardes data=$config selected=0}>Aucun — les sauvegardes automatiques sont désactivées</option>
                <option value="1"{form_field name=frequence_sauvegardes data=$config selected=1}>Quotidien, tous les jours</option>
                <option value="7"{form_field name=frequence_sauvegardes data=$config selected=7}>Hebdomadaire, tous les 7 jours</option>
                <option value="15"{form_field name=frequence_sauvegardes data=$config selected=15}>Bimensuel, tous les 15 jours</option>
                <option value="30"{form_field name=frequence_sauvegardes data=$config selected=30}>Mensuel</option>
                <option value="90"{form_field name=frequence_sauvegardes data=$config selected=90}>Trimestriel</option>
                <option value="365{form_field name=frequence_sauvegardes data=$config selected=365}">Annuel</option>
            </select>
        </dd>
        <dt><label for="f_max_backups">Nombre de sauvegardes conservées</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
        <dd class="help">
            Par exemple avec l'intervalle mensuel, en indiquant de conserver 12 sauvegardes,
            vous pourrez garder un an d'historique de sauvegardes.
        </dd>
        <dd class="help">
            <strong>Attention :</strong> si vous choisissez un nombre important et un intervalle réduit,
            l'espace disque occupé par vos sauvegardes va rapidement augmenter.
        </dd>
        <dd><input type="number" name="nombre_sauvegardes" value="{form_field name=nombre_sauvegardes data=$config}" if="f_max_backups" min="1" max="90" required="required" /></dd>
    </dl>
    <p>
        {csrf_field key="backup_config"}
        <input type="submit" name="config" value="Enregistrer &rarr;" />
    </p>
</fieldset>

</form>
<form method="post" action="{$self_url_no_qs}">

<fieldset>
    <legend>Sauvegarde manuelle</legend>
    <p>
        {csrf_field key="backup_create"}
        <input type="submit" name="create" value="Créer une nouvelle sauvegarde des données &rarr;" />
    </p>
</fieldset>

</form>
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
                    <option value="{$f}">{$f} — {$d|date_fr:'d/m/Y à H:i'}</option>
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
            <input type="submit" name="restore" value="Restaurer cette sauvegarde" />
            <input type="submit" name="remove" value="Supprimer cette sauvegarde" />
        </p>
    {/if}
</fieldset>

</form>
<form method="post" action="{$self_url_no_qs}">

<fieldset>
    <legend>Téléchargement</legend>
    <p>
        {csrf_field key="backup_download"}
        <input type="submit" name="download" value="Télécharger une copie des données sur mon ordinateur" />
    </p>
</fieldset>

</form>
<form method="post" action="{$self_url_no_qs}" enctype="multipart/form-data">

<fieldset>
    <legend><label for="f_file">Restaurer depuis un fichier</label></legend>
    <p class="alert">
        Attention, l'intégralité des données courantes seront effacées et remplacées par celles
        contenues dans le fichier fourni.
    </p>
    <p class="help">
        Une sauvegarde des données courantes sera effectuée avant le remplacement,
        en cas de besoin d'annuler cette restauration.
    </p>
    <p>
        {csrf_field key="backup_restore"}
        <input type="hidden" name="MAX_FILE_SIZE" value="{$max_file_size}" />
        <input type="file" name="file" id="f_file" required="required" />
        (maximum {$max_file_size|format_bytes})
        <input type="submit" name="restore_file" value="Restaurer depuis le fichier sélectionné &rarr;" />
    </p>
    {if $code && ($code == Garradin\Sauvegarde::INTEGRITY_FAIL && Garradin\ALLOW_MODIFIED_IMPORT)}
    <p>
        <label><input type="checkbox" name="force_import" value="1" /> Ignorer les erreurs, je sais ce que je fait</label>
    </p>
    {/if}
</fieldset>

</form>

{include file="admin/_foot.tpl"}