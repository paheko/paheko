{include file="admin/_head.tpl" title="Supprimer un compte" current="acc/charts"}

{include file="common/delete_form.tpl"
	legend="Supprimer ce plan comptable ?"
	warning="Êtes-vous sûr de vouloir supprimer le compte « %s — %s » ?"|args:$account.code,$account.label
	alert="Attention, le compte ne pourra pas être supprimé si des écritures y sont affectées (sauf en tant que compte analytique)."
	csrf_key="acc_accounts_delete_%s"|args:$account.id
}

{include file="admin/_foot.tpl"}