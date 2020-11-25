{include file="admin/_head.tpl" title="Sauvegarde et restauration" current="config"}

{include file="admin/config/_menu.tpl" current="donnees"}

{include file="admin/config/donnees/_menu.tpl" current="index"}

{form_errors}

{if $code == Sauvegarde::INTEGRITY_FAIL && ALLOW_MODIFIED_IMPORT}
    <p class="block alert">Pour passer outre, renvoyez le fichier en cochant la case «&nbsp;Ignorer les erreurs&nbsp;».
    Attention, si vous avez effectué des modifications dans la base de données, cela peut créer des bugs&nbsp;!</p>
{/if}

{if $ok}
    <p class="block confirm">
        {if $ok == 'restore'}La restauration a bien été effectuée. Si vous désirez revenir en arrière, vous pouvez utiliser la sauvegarde automatique nommée <em>{$now_date}.avant_restauration.sqlite</em>, sinon vous pouvez l'effacer.
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
    <legend>Téléchargement d'une sauvegarde</legend>
	<p class="help">
		Info : la base de données fait actuellement {$db_size|format_bytes} (dont {$files_size|format_bytes} pour les documents et images).
	</p>
    <p class="submit">
        {csrf_field key="backup_download"}
        {button type="submit" name="download" label="Télécharger une copie de la base de données sur mon ordinateur" shape="download" class="main"}
    </p>
</fieldset>

</form>

<form method="post" action="{$self_url_no_qs}" enctype="multipart/form-data">

<fieldset>
    <legend><label for="f_file">Restaurer depuis un fichier de sauvegarde</label></legend>
    <p class="block alert">
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
    </p>
    <p class="submit">
        {button type="submit" name="restore_file" label="Restaurer depuis le fichier sélectionné" shape="upload" class="main"}
    </p>
    {if $code && ($code == Sauvegarde::INTEGRITY_FAIL && ALLOW_MODIFIED_IMPORT)}
    <p>
        {input type="checkbox" name="force_import" value="1" label="Ignorer les erreurs, je sais ce que je fait"}
    </p>
    {/if}
</fieldset>

</form>

{include file="admin/_foot.tpl"}