{include file="_head.tpl" title="Suppression des anciennes versions" current="config"}

{include file="config/_menu.tpl"}

{assign var="size_bytes" value=$disk_use|size_in_bytes}

{include file="common/delete_form.tpl"
	legend="Supprimer les anciennes versions ?"
	warning="Libérer %s d'espace disque en supprimant toutes les anciennes versions ?"|args:$size_bytes
	alert="Après cette action, seule la dernière version de chaque fichier sera conservée."
	info="Même les versions nommées seront supprimées."}

{include file="_foot.tpl"}