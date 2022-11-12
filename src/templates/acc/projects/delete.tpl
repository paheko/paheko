{include file="_head.tpl" title="Supprimer un projet" current="acc/years"}

{include file="common/delete_form.tpl"
	legend="Supprimer ce projet ?"
	warning="Êtes-vous sûr de vouloir supprimer le projet « %s » ?"|args:$project.label
	info="Le contenu des écritures comptables ne sera pas modifiées, seule l'affectation à ce projet sera supprimée."}

{include file="_foot.tpl"}