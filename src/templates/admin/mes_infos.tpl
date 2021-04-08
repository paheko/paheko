{include file="admin/_head.tpl" title="Mes informations personnelles" current="mes_infos"}

<nav class="tabs">
    <ul>
        <li class="current"><a href="{$admin_url}mes_infos.php">Mes informations personnelles</a></li>
        <li><a href="{$admin_url}mes_infos_securite.php">Mot de passe et options de sécurité</a></li>
    </ul>
</nav>

<dl class="describe">
    <dd>
        {linkbutton href="mes_infos_modifier.php" label="Modifier mes informations" shape="edit"}
    </dd>
</dl>

{include file="admin/membres/_details.tpl" champs=$champs data=$data show_message_button=false mode="user"}

{include file="admin/_foot.tpl"}