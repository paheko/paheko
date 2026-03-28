<?php $sql_current = $current; ?>
{include file="config/_menu.tpl" current="advanced" sub_current="sql"}

<nav class="tabs">
	<ul class="sub">
		{tabitem href="./" label="Tables" name="tables" selected=$sql_current}
		{tabitem href="./diagram.php" label="Diagramme" name="diagram" selected=$sql_current}
		{tabitem href="./query.php" label="Requête SQL" name="query" selected=$sql_current}
		{tabitem href="./options.php" label="Options développeur⋅euse" name="options" selected=$sql_current}
	</ul>
</nav>
