{include file="admin/_head.tpl" title="Inclure un fichier" current="wiki" body_id="transparent" is_popup=true js=1}

<form method="post" enctype="multipart/form-data" action="{$self_url|escape}" id="f_upload">
    <fieldset>
        <legend>Téléverser un fichier</legend>
        <input type="hidden" name="MAX_FILE_SIZE" value="{$max_size|escape}" id="f_maxsize" />
        <dl>
            <dd class="help">Taille maximale : {$max_size|format_bytes}</dd>
            <dd class="fileUpload"><input type="file" name="fichier" id="f_fichier" data-hash-check /></dd>
        </dl>
        <p class="submit">
            {csrf_field key="wiki_upload_`$page.id`"}
            <input type="submit" id="f_submit" value="Envoyer le fichier" />
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
            <p class="actions">
                <a href="{$file.url|escape}" onclick="return !window.open(this.href);" class="icn" title="Télécharger">⇓</a>
                <a href="?delete={$file.id|escape}" class="icn" title="Supprimer">✘</a>
            </p>
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
            <td>{$file.type|escape}</td>
            <td class="actions">
                <a href="{$file.url|escape}" onclick="return !window.open(this.href);" class="icn" title="Télécharger">⇓</a>
                <a href="?delete={$file.id|escape}" class="icn" title="Supprimer">✘</a>
            </td>
        </tr>
    {/foreach}
    </tbody>
</table>
{/if}

<script type="text/javascript">
{literal}
uploadHelper($('#f_fichier'), {
    width: 1920,
    height: null,
    resize: true,
    bytes: 'o',
    size_error_msg: 'Le fichier %file fait %size, soit plus que la taille maximale autorisée de %max_size.'
});

function insertImageHelper(file, from_upload) {
    if (!document.querySelectorAll)
    {
        window.parent.te_insertImage(file.id, 'centre');
        return true;
    }

    var f = document.getElementById('insertImage');
    f.style.display = 'block';

    var inputs = f.querySelectorAll('input[type=button]');

    for (var i = 0; i < inputs.length; i++)
    {
        inputs[i].onclick = function(e) {
            window.parent.te_insertImage(file.id, e.target.name, f.f_caption.value);
        };
    }

    f.querySelector('dd.image').innerHTML = '';
    var img = document.createElement('img');
    img.src = file.thumb;
    img.alt = '';
    f.querySelector('dd.image').appendChild(img);

    f.querySelector('dd.cancel input[type=reset]').onclick = function() {
        f.style.display = 'none';

        if (from_upload)
        {
            location.href = location.href;
        }
    };
}

function insertHelper(data) {
    var file = (data.file || data);

    if (file.image)
    {
        insertImageHelper(file, true);
    }
    else
    {
        window.parent.te_insertFile(data.file.id);
    }

    return true;
}

var gallery = document.getElementsByClassName('gallery');

if (gallery.length == 1 && document.querySelector)
{
    gallery = gallery[0];

    var items = gallery.getElementsByTagName('li');

    for (var i = 0; i < items.length; i++)
    {
        var a = items[i].querySelector('figure > a');
        a.onclick= function (e) {
            insertImageHelper({
                id: this.getAttribute('data-id'),
                thumb: this.firstChild.src
            });
            return false;
        };
    }
}

//insertImageHelper({})
{/literal}
</script>

{include file="admin/_foot.tpl"}