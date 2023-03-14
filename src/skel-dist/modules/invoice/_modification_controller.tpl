{{#restrict block=true section="accounting" level="write"}}
	
{{if $_GET.action === 'sign' || $_POST.signing_submit || $_GET.action === 'delete'}}
	{{if !$_GET.id}}
		{{:assign var='check_errors.' value='Aucun devis sélectionné.'}}
	{{else}}
		{{#load id=$_GET.id|intval module=$module.name}}
		{{if $status !== $DRAFT_STATUS}}
			{{:assign var='check_errors.' value='Seuls les brouillons peuvent être signés ou supprimés.'}}
		{{elseif $cancelled}}
			{{:assign var='check_errors.' value='Les documents annulés ne peuvent pas être signés ou supprimés.'}}
		{{elseif $_POST.signing_submit}}
		
			{{if !$_POST.signing_place}}
				{{:assign var='check_errors.' value='Lieu de la signature obligatoire.'}}
			{{/if}}
			{{:include file='./include/check_max_length.tpl' keep='check_errors' check_label='Lieu de la signature trop long' check_value=$_POST.signing_place check_max=128}}
			{{if !$_POST.signing_date}}
				{{:assign var='check_errors.' value='Date de la signature obligatoire.'}}
			{{/if}}
			{{:include file='./include/check_max_length.tpl' keep='check_errors' check_label='Date de la signature trop longue' check_value=$_POST.signing_date check_max=10}}
			{{:assign signing_date=$_POST.signing_date|date:'Y-m-d'}}
			{{if $_POST.signing_date|date_short === null}} {{* Means data is not a date *}}
				{{:assign var='check_errors.' value="La date de signature doit être une date."}}
			{{/if}}
			{{if $signing_date < $date}}
				{{:assign formated_date=$date|date_short}}
				{{:assign var='check_errors.' value="La signature du devis doit se situer après la date du devis (%s)."|args:$formated_date}}
			{{/if}}
			{{if !$check_errors}}
				{{:save id=$_GET.id|intval
					validate_schema="./include/schema/quotation.json"
					key=$key
					type=$type
					recipient_business_name=$recipient_business_name
					recipient_address=$recipient_address
					recipient_member_id=$recipient_member_id
					recipient_member_numero=$recipient_member_numero
					introduction_text=$introduction_text
					subject=$subject
					date=$date
					deadline=$deadline
					status=$AWAITING_STATUS
					cancelled=$cancelled
					items=$items
					total=$total
					vat_exemption=$vat_exemption
					siret=$siret
					org_contact=$org_contact
					author_id=$author_id
					last_modification_date=$now|atom_date
					signing_place=$_POST.signing_place
					signing_date=$signing_date
					payment_detail=$payment_detail
					extra_info=$extra_info
					module_version=$VERSION
				}}

				{{:http redirect="details.html?id=%d&ok=1"|args:$id}}
			{{/if}}

		{{elseif $_GET.action === 'delete'}}
			{{:delete id=$_GET.id|intval}}
			{{:http redirect="index.html?ok=3"}}
		{{/if}}
		{{/load}}
	{{/if}}

{{elseif $_POST.reject_submit || $_POST.validate_submit}}
	{{if !$_GET.id}}
		{{:assign var='check_errors.' value='Aucun devis sélectionné.'}}
	{{else}}
		{{#load id=$_GET.id|intval module=$module.name}}
		{{if $status !== $AWAITING_STATUS}}
			{{:assign var='check_errors.' value='Seuls les devis en attente peuvent être validés ou refusés.'}}
		{{elseif $cancelled}}
			{{:assign var='check_errors.' value='Les devis annulés ne peuvent pas être validés ou refusés.'}}
		{{/if}}
		{{if $_POST.reject_submit}}
			{{:assign new_status=$REJECTED_STATUS}}
			{{:assign redirection_code=4}}
		{{elseif $_POST.validate_submit}}
			{{:assign new_status=$VALIDATED_STATUS}}
			{{:assign validation_date=$now|date:'Y-m-d'}}
			{{:assign redirection_code=5}}
		{{/if}}
		{{if !$check_errors}}
			{{if $_POST.validate_submit && $_POST.invoice}}

				{{#load select="MAX(key) AS last" where="json_extract(document, '$.type') = :type" :type=$INVOICE_TYPE module=$module.name}} 
					{{:assign last_numeric=$last|regexp_replace:'~\D~':''}}
					{{:assign next_numeric='%d+1'|math:$last_numeric}}
					{{:assign invoice_key="F%06d"|args:$next_numeric}}
				{{/load}}

				{{:save assign_new_id='invoice_id'
					validate_schema="./include/schema/invoice.json"
					key=$invoice_key
					type=$INVOICE_TYPE
					recipient_business_name=$recipient_business_name
					recipient_address=$recipient_address
					recipient_member_id=$recipient_member_id
					recipient_member_numero=$recipient_member_numero
					introduction_text=null
					subject=$subject
					date=$now|date:'Y-m-d'
					deadline=null
					status=$AWAITING_STATUS
					cancelled=false
					items=$items
					total=$total
					vat_exemption=$vat_exemption
					siret=$siret
					org_contact=$org_contact
					author_id=$logged_user.id|intval
					parent_id=$_GET.id|intval
					last_modification_date=$now|atom_date
					signing_place=$signing_place
					signing_date=$now|date:'Y-m-d'
					validation_date=null
					payment_detail=null
					extra_info=null
					module_version=$VERSION
				}}
			{{/if}}

			{{:save id=$_GET.id|intval
				validate_schema="./include/schema/quotation.json"
				key=$key
				type=$type
				recipient_business_name=$recipient_business_name
				recipient_address=$recipient_address
				recipient_member_id=$recipient_member_id
				recipient_member_numero=$recipient_member_numero
				introduction_text=$introduction_text
				subject=$subject
				date=$date
				deadline=$deadline
				status=$new_status
				cancelled=$cancelled
				items=$items
				total=$total
				vat_exemption=$vat_exemption
				siret=$siret
				org_contact=$org_contact
				author_id=$author_id
				child_id=$invoice_id|intval
				last_modification_date=$now|atom_date
				signing_place=$signing_place
				signing_date=$signing_date
				validation_date=$validation_date
				payment_detail=$payment_detail
				extra_info=$extra_info
				parent_id=$_GET.id|intval
				module_version=$VERSION
			}}

			{{:http redirect="details.html?id=%d&ok=%d"|args:$id:$redirection_code}}
		{{/if}}
		{{/load}}
	{{/if}}

{{elseif $_POST.mark_as_paid_submit}}
	{{#load id=$_GET.id|intval}}{{:assign .='invoice'}}{{/load}}
	{{if $invoice.status !== $AWAITING_STATUS}}
		{{:assign var='check_errors.' value='Seuls les factures "en attente de validation" peuvent être payées.'}}
	{{elseif $invoice.cancelled}}
			{{:assign var='check_errors.' value='Les factures annulées ne peuvent pas être marquées comme payées.'}}
	{{/if}}

	{{:assign date=$_POST.date|date:'Y-m-d'}}
	{{if !$_POST.date}}
		{{:assign var='check_errors.' value="La date est obligatoire."}}
	{{/if}}
	{{if $_POST.date|parse_date === null}} {{* Means data is not a date *}}
		{{:assign var='check_errors.' value="La date du paiement doit être une date."}}
	{{/if}}
	{{if $date < $invoice.date}}
		{{:assign formatted_invoice_date=$invoice.date|date_short}}
		{{:assign var='check_errors.' value="Le paiement doit se situer après la date d'émission de la facture (%s)."|args:$formatted_invoice_date}}
	{{/if}}
	
	{{:include file='./include/check_max_length.tpl' check_value=$_POST.comment check_max=256 check_label='Remarques trop longues' keep='check_errors'}}

	{{:include file='./include/shim/array_last_num_key.tpl' keep='transaction_id, check_errors' array=$_POST.transaction name='transaction_id' error_message='Écriture séléctionnée invalide.'}}
	{{#transactions id=$transaction_id|intval}}{{:assign .='transaction'}}{{/transactions}}
	{{if $transaction_id && !$transaction}}
		{{:assign var='check_errors.' value="Transaction sélectionnée invalide."}}
	{{/if}}

	{{if !$check_errors}}
		{{:save id=$invoice.id|intval
			validate_schema="./include/schema/invoice.json"
			key=$invoice.key
			type=$invoice.type
			recipient_business_name=$invoice.recipient_business_name
			recipient_address=$invoice.recipient_address
			recipient_member_id=$invoice.recipient_member_id
			recipient_member_numero=$invoice.recipient_member_numero
			introduction_text=null
			subject=$invoice.subject
			date=$invoice.date
			deadline=null
			status=$PAID_STATUS
			cancelled=$invoice.cancelled
			items=$invoice.items
			total=$invoice.total
			vat_exemption=$invoice.vat_exemption
			siret=$invoice.siret
			org_contact=$invoice.org_contact
			author_id=$invoice.author_id
			parent_id=$invoice.parent_id
			last_modification_date=$now|atom_date
			signing_place=null
			signing_date=$invoice.signing_date
			validation_date=null
			payment_date=$date
			payment_comment=$_POST.comment
			transaction_id=$transaction.id|intval
			payment_detail=null
			extra_info=null
			module_version=$VERSION
		}}
		{{:http redirect="details.html?id=%d&ok=6&show=invoice"|args:$invoice.id}}
	{{/if}}

{{elseif $_POST.cancel_submit}}
	{{if !$_GET.id}}
		{{:assign var='check_errors.' value='Aucun document sélectionné.'}}
	{{else}}
		{{#load id=$_GET.id}}
			{{if $status === $DRAFT_STATUS}}
				{{:assign var='check_errors.' value='Les brouillons ne peuvent pas être annulés. Vous pouvez néanmoins les supprimer.'}}
			{{elseif $cancelled}}
				{{:assign var='check_errors.' value='Le document est déjà annulé.'}}
			{{/if}}
			{{if !$check_errors}}
				{{if $type === $INVOICE_TYPE}}
					{{:save id=id|intval
						validate_schema="./include/schema/invoice.json"
						key=$key
						type=$type
						recipient_business_name=$recipient_business_name
						recipient_address=$recipient_address
						recipient_member_id=$recipient_member_id
						recipient_member_numero=$recipient_member_numero
						introduction_text=$introduction_text
						subject=$subject
						date=$date
						deadline=$deadline
						status=$status
						cancelled=true
						items=$items
						total=$total
						vat_exemption=$vat_exemption
						siret=$siret
						org_contact=$org_contact
						author_id=$author_id
						parent_id=$parent_id
						last_modification_date=$now|atom_date
						signing_place=$signing_place
						signing_date=$signing_date
						validation_date=$validation_date
						payment_date=$payment_date
						payment_comment=$comment
						transaction_id=$transaction_id
						payment_detail=$payment_detail
						extra_info=$extra_info
						module_version=$VERSION
					}}
				{{else}}
					{{:save id=$id|intval
						validate_schema="./include/schema/quotation.json"
						key=$key
						type=$type
						recipient_business_name=$recipient_business_name
						recipient_address=$recipient_address
						recipient_member_id=$recipient_member_id
						recipient_member_numero=$recipient_member_numero
						introduction_text=$introduction_text
						subject=$subject
						date=$date
						deadline=$deadline
						status=$status
						cancelled=true
						items=$items
						total=$total
						vat_exemption=$vat_exemption
						siret=$siret
						org_contact=$org_contact
						author_id=$author_id
						child_id=$child_id
						last_modification_date=$now|atom_date
						signing_place=$signing_place
						signing_date=$signing_date
						validation_date=$validation_date
						payment_detail=$payment_detail
						extra_info=$extra_info
						parent_id=$parent_id
						module_version=$VERSION
					}}
				{{/if}}
				{{:http redirect="index.html?id=%d&ok=5&show=%s"|args:$id:$type}}
			{{/if}}
		{{/load}}
	{{/if}}
{{/if}}

{{if $_POST.status_update_button}} {{* Only for developers *}}
	{{if !$_POST.id}}
		{{:error message='Aucun devis sélectionné.'}}
	{{else}}
		{{:assign var='new_status_label' from='INVOICE_STATUS_LABELS.%s'|args:$_POST.status}}
		{{if $new_status_label === null}}
			{{:assign var='check_errors.' value='Nouveau statut invalide : %s.'|args:$_POST.status}}
		{{/if}}
		{{if !$check_errors}}
			{{#load id=$_POST.id|intval module=$module.name}}
				{{:assign var='allowed_type' from='DOCUMENT_TYPES.%s'|args:$type}}
				{{if !$allowed_type}}
					{{:assign var='check_errors.' value='Type invalide : %s!'|args:$type}}
				{{else}}
					{{:assign cancelled=$_POST.cancelled|boolval}}
					{{:save id=$id
						validate_schema="./include/schema/%s.json"|args:$type
						key=$key
						type=$type
						recipient_business_name=$recipient_business_name
						recipient_address=$recipient_address
						recipient_member_id=$recipient_member_id
						recipient_member_numero=$recipient_member_numero
						introduction_text=$introduction_text
						subject=$subject
						date=$date
						deadline=$deadline
						status=$_POST.status
						cancelled=$cancelled
						items=$items
						total=$total
						vat_exemption=$vat_exemption
						siret=$siret
						org_contact=$org_contact
						author_id=$author_id
						parent_id=$parent_id
						last_modification_date=$now|atom_date
						signing_place=$signing_place
						signing_date=$signing_date
						validation_date=$validation_date
						payment_date=$payment_date
						payment_comment=$payment_comment
						transaction_id=$transaction_id
						payment_detail=$payment_detail
						extra_info=$extra_info
						parent_id=$parent_id
						module_version=$VERSION
					}}
					{{:http redirect="details.html?id=%d&ok=2&show=%s"|args:$id:$type}}
				{{/if}}
			{{/load}}
		{{/if}}
	{{/if}}
{{/if}}
{{/restrict}}
