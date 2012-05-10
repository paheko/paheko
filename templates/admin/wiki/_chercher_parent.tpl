{include file="admin/_head.tpl" title="Choisir la page parent" current="wiki" is_popup=true}

<ul>
    {foreach from=$list item="rub"}
    <li>
        <h3><a href="?current={$rub.id|escape}">{$rub.titre|escape}</a></h3>
    </li>
    {/foreach}
</ul>

{literal}
<script type="text/javascript">
(function() {
    window.chooseParent = function(parent, title)
    {
        window.opener.changeParent(id, title);
        self.close();
        return false;
    };
}());
</script>
{/literal}

{include file="admin/_foot.tpl"}