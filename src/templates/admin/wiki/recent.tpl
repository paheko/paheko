{include file="admin/_head.tpl" title="Pages modifiées récemment" current="wiki/recent"}

{if !empty($list)}
    <table class="list">
        <tbody>
        {foreach from=$list item="page"}
        <tr>
            <th><a href="{$www_url}admin/wiki/?{$page.uri}">{$page.titre}</a></th>
            <td>{$page.date_modification|date_fr:'d/m/Y à H:i'}</td>
        </tr>
        {/foreach}
        </tbody>
    </table>

    {pagination url="?p=[ID]" page=$current_page bypage=$bypage total=$total}
{else}
    <p class="alert">Pas de modification récente.</p>
{/if}

{include file="admin/_foot.tpl"}