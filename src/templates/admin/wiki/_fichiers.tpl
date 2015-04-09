{include file="admin/_head.tpl" title="Inclure un fichier" current="wiki" body_id="transparent" is_popup=true js=1}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" enctype="multipart/form-data" action="{$self_url|escape}" id="f_upload">
    <fieldset>
        <legend>Téléverser un fichier</legend>
        <input type="hidden" name="MAX_FILE_SIZE" value="{$max_size|escape}" id="f_maxsize" />
        <dl>
            <dd class="help">Taille maximale : {$max_size|format_bytes}</dd>
            <dd class="fileUpload"><input type="file" name="fichier" id="f_fichier" data-hash-check /></dd>
        </dl>
        <p class="submit">
            <input type="hidden" name="{$csrf_field_name|escape}" value="{$csrf_value|escape}" />
            <input type="submit" name="upload" id="f_submit" value="Envoyer le fichier" />
        </p>
    </fieldset>
</form>

<form method="get" action="#" style="display: none;" id="insertImage">
    <fieldset>
        <h3>Insérer une image dans le texte</h3>
        <dl>
            <dd class="image"></dd>
            <dt>Légende <i>(facultatif)</i></dt>
            <dd class="caption">
                <input type="text" name="f_caption" size="50" />
            </dd>
            <dt>Alignement&nbsp;:</dt>
            <dd class="align">
                <input type="button" name="gauche" value="À gauche" />
                <input type="button" name="centre" value="Au centre" />
                <input type="button" name="droite" value="À droite" />
            </dd>
            <dd class="cancel">
                <input type="reset" value="Annuler" />
            </dd>
        </dl>
    </fieldset>
</form>

{if !empty($images)}
<ul class="gallery">
{foreach from=$images item="file"}
    <li>
        <figure>
            <a href="{$file.url|escape}" data-id="{$file.id}"><img src="{$file.thumb|escape}" alt="" title="{$file.nom|escape}" /></a>
            <form class="actions" method="post" action="{$self_url|escape}">
                <a href="{$file.url|escape}" onclick="return !window.open(this.href);" class="icn" title="Télécharger">⇓</a>
                <input type="hidden" name="{$csrf_field_name|escape}" value="{$csrf_value|escape}" />
                <input type="hidden" name="delete" value="{$file.id|escape}" />
                <noscript><input type="submit" value="Supprimer" /></noscript>
            </form>
        </figure>
    </li>
{/foreach}
</ul>
{/if}

{if !empty($fichiers)}
<table class="list">
    <tbody>
    {foreach from=$fichiers item="file"}
        <tr>
            <th>{$file.nom|escape}</th>
            <td>{if $file.type}{$file.type|escape}{/if}</td>
            <td class="actions">
                <form class="actions" method="post" action="{$self_url|escape}">
                    <a href="{$file.url|escape}" onclick="return !window.open(this.href);" class="icn" title="Télécharger">⇓</a>
                    <input type="hidden" name="{$csrf_field_name|escape}" value="{$csrf_value|escape}" />
                    <input type="hidden" name="delete" value="{$file.id|escape}" />
                    <noscript><input type="submit" value="Supprimer" /></noscript>
                </form>
            </td>
        </tr>
    {/foreach}
    </tbody>
</table>
{/if}

{include file="admin/_foot.tpl"}