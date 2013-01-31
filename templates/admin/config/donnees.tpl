{include file="admin/_head.tpl" title="Données — Sauvegarde et restauration" current="config"}

{include file="admin/config/_menu.tpl" current="donnees"}

<form method="post" action="{$self_url|escape}">

<fieldset>
    <legend>Sauvegarde automatique</legend>
    <p class="help">
        En activant cette option une sauvegarde sera automatiquement créée à chaque intervalle donné.
        Par exemple en activant une sauvegarde hebdomadaire, une copie des données sera réalisée
        une fois par semaine, sauf si aucune modification n'a été effectuée sur les données
        ou que personne ne s'est connecté.
    </p>
    <dl>
        <dt><label for="f_frequency">Intervalle de sauvegarde</label></dt>
        <dd>
            <select name="frequency" id="f_frequency">
                <option value="0">Aucun — les sauvegardes automatiques sont désactivées</option>
                <option value="1">Quotidien</option>
                <option value="7">Hebdomadaire</option>
                <option value="15">Bi-hebdomadaire</option>
                <option value="30">Mensuel</option>
                <option value="90">Trimestriel</option>
                <option value="365">Annuel</option>
            </select>
        </dd>
        <dt><label for="f_max_backups">Nombre de sauvegardes conservées</label></dt>
        <dd class="help">
            Par exemple avec l'intervalle mensuel, en indiquant de conserver 12 sauvegardes,
            vous pourrez garder un an d'historique de sauvegardes.
        </dd>
        <dd class="help">
            <strong>Attention :</strong> si vous choisissez un nombre important et un intervalle réduit,
            l'espace disque occupé par vos sauvegardes va rapidement augmenter.
        </dd>
        <dd><input type="number" name="keep_max" if="f_max_backups" min="1" max="90" /></dd>
    </dl>
    <p>
        <input type="submit" name="backup" value="Créer une nouvelle sauvegarde des données" />
    </p>
</fieldset>

</form>
<form method="post" action="{$self_url|escape}">

<fieldset>
    <legend>Copies de sauvegarde disponibles</legend>
    {if empty($liste)}
        <p class="help">Aucune copie de sauvegarde disponible.</p>
    {else}
        <dl>
        {foreach from=$liste item="f"}
            <dd>
                <label>
                    <input type="radio" name="file" value="{$f|escape}" />
                    {$f|escape}
                </label>
            </dd>
        {/foreach}
        </dl>
        <p>
            <input type="submit" name="restore" value="Restaurer cette sauvegarde" />
            <input type="submit" name="remove" value="Supprimer cette sauvegarde" />
        </p>
    {/if}
</fieldset>

</form>
<form method="post" action="{$self_url|escape}">

<fieldset>
    <legend>Sauvegarde</legend>
    <p>
        <input type="submit" name="backup" value="Créer une nouvelle sauvegarde des données" />
    </p>
</fieldset>

</form>
<form method="post" action="{$self_url|escape}">

<fieldset>
    <legend>Téléchargement</legend>
    <p>
        <input type="submit" name="download" value="Télécharger une copie des données sur mon ordinateur" />
    </p>
</fieldset>

<form method="post" action="{$self_url|escape}">
</form>

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
        <input type="file" name="file" id="f_file" />
        <input type="submit" name="restore_file" value="Restaurer depuis le fichier sélectionné" />
    </p>
</fieldset>

</form>

{include file="admin/_foot.tpl"}