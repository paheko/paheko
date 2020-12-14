{include file="admin/_head.tpl" title="Choisir la page parent" current="wiki" body_id="popup" is_popup=true}

<div class="wikiTree">
    <p class="choice">
        <input type="button" onclick="chooseParent();" value="Choisir la page sélectionnée" />
    </p>

    {display_tree tree=$list}

</div>

{literal}
<script type="text/javascript">
(function() {
    window.chooseParent = function()
    {
        var elm = document.getElementsByClassName("current")[0].getElementsByTagName("a")[0];

        if (match = elm.href.match(/=(\d+)$/))
        {
            var id = parseInt(match[1], 10);
            var titre = (id == 0 ? 'la racine du site' : elm.innerHTML);

            if (window.opener.changeParent(id, titre))
            {
                self.close();
            }
            else
            {
                alert("Impossible de choisir la page comme parent d'elle-même !");
            }

            return false;
        }
    };
}());
</script>
{/literal}

{include file="admin/_foot.tpl"}