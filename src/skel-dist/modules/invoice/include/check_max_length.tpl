{{if $check_value|strlen > $check_max}}
	{{:assign length=$check_value|strlen}}
	{{:assign var='check_errors.' value="%s. %d caractères sur les %d autorisés."|args:$check_label:$length:$check_max}}
{{/if}}
