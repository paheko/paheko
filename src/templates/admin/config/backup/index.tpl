{include file="admin/_head.tpl" title="Sauvegardes" current="config"}

{include file="admin/config/_menu.tpl" current="backup"}

{include file="admin/config/backup/_menu.tpl" current="index"}

<fieldset>
	<legend>Politique de sauvegardes</legend>
	{if ENABLE_AUTOMATIC_BACKUPS && !$config.frequence_sauvegardes}
	<p class="help block">
		Les <a href="save.php">sauvegardes automatiques</a> sont désactivées. Il est recommandé de les activer pour pouvoir revenir en arrière en cas de problème majeur. Attention, cela ne dispense pas de réaliser des sauvegardes régulières sur votre ordinateur.
	</p>
	{/if}

	<p class="help">
		En cas de problème sur le serveur (plantage, dysfonctionnement du disque dur, incendie, etc.) vous pourriez perdre vos données.<br />
		<strong>Il est donc recommandé de réaliser régulièrement des sauvegardes et de les conserver sur votre ordinateur personnel&nbsp;!</strong><br /><br />
		Pour cela il convient de se rendre dans la section <a href="save.php">Sauvegarder</a> et de cliquer sur le bouton <em>«&nbsp;Télécharger une copie de la base de données sur mon ordinateur&nbsp;»</em>.
	</p>
</fieldset>

<fieldset>
	<legend>Import et export</legend>
	<p class="help">
		Il est possible d'exporter et importer des données afin d'interagir avec des logiciels tiers. Cette liste regroupe les imports et exports les plus courants. Il est également possible d'exporter la plupart des listes qui comportent un bouton "Exporter".
	</p>
	<dl>
		<dt><strong>Membres</strong></dt>
		<dd><a href="{$admin_url}membres/import.php">Import de la liste des membres</a></dd>
		<dd><a href="{$admin_url}membres/import.php?export=ods">Export de la liste des membres au format tableur LibreOffice Calc / Excel</a></dd>
		<dd><a href="{$admin_url}membres/import.php?export=csv">Export de la liste des membres au format CSV</a></dd>
		<dt><strong>Comptabilité</strong> (pour l'exercice courant)</dt>
		<dd><a href="{$admin_url}acc/years/import.php">Import des données comptables</a></dd>
		<dd><a href="{$admin_url}acc/years/export.php">Export des données comptables</a></dd>
	</dl>
</fieldset>

{include file="admin/_foot.tpl"}