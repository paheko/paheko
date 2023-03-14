{{if $pattern === null && $type === $QUOTATION_TYPE}}
	{{:assign pattern="D%06d"}}
{{elseif $pattern === null && $type === $INVOICE_TYPE}}
	{{:assign pattern="F%06d"}}
{{/if}}

{{if !$assign_to}}
	{{:assign assign_to='key'}}
{{/if}}

{{#load select="MAX(key) AS last" where="json_extract(document, '$.type') = :type" :type=$type}} 
	{{:assign last_numeric=$last|regexp_replace:'~\D~':''}}
	{{:assign next_numeric='%d+1'|math:$last_numeric}}
	{{:assign var=$assign_to value=$pattern|args:$next_numeric}}
{{/load}}

{{:debug type=$type}}
{{:debug to=$assign_to}}
{{:debug key=$key}}
{{:debug invoice_key=$invoice_key}}