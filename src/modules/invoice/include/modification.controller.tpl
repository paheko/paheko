{{#restrict block=true section="accounting" level="write"}}
	
{{if $_GET.action === 'sign' || $_POST.signing_submit || $_GET.action === 'delete'}}
	{{if !$_GET.id}}
		{{:assign var='check_errors.' value='Aucun devis sélectionné.'}}
	{{else}}
		{{#load id=$_GET.id|intval}}
		{{if $status !== $DRAFT_STATUS}}
			{{:assign var='check_errors.' value='Seuls les brouillons peuvent être signés ou supprimés.'}}
		{{elseif $cancelled}}
			{{:assign var='check_errors.' value='Les documents annulés ne peuvent pas être signés ou supprimés.'}}
		{{elseif $_POST.signing_submit}}
		
			{{if !$_POST.signing_place}}
				{{:assign var='check_errors.' value='Lieu de la signature obligatoire.'}}
			{{/if}}
			{{:include file='./check_max_length.tpl' keep='check_errors' check_label='Lieu de la signature trop long' check_value=$_POST.signing_place check_max=128}}
			{{if !$_POST.signing_date}}
				{{:assign var='check_errors.' value='Date de la signature obligatoire.'}}
			{{/if}}
			{{:include file='./check_max_length.tpl' keep='check_errors' check_label='Date de la signature trop longue' check_value=$_POST.signing_date check_max=10}}
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
					validate_schema="./schema/quotation.json"
					validate_only="status, signing_place, signing_date, last_modification_date"
					status=$AWAITING_STATUS
					signing_place=$_POST.signing_place
					signing_date=$signing_date
					last_modification_date=$now|atom_date
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
		{{#load id=$_GET.id|intval}}
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
				{{:include file='./generate_next_key.tpl' type=$INVOICE_TYPE assign_to='invoice_key' keep='invoice_key'}}

				{{:save assign_new_id='invoice_id'
					validate_schema="./schema/invoice.json"
					key=$invoice_key
					type=$INVOICE_TYPE
					status=$AWAITING_STATUS
					cancelled=false
					cancellation_reason=null
					author_id=$logged_user.id|intval
					parent_id=$_GET.id|intval
					duplicated_from_id=null
					transaction_id=null
					subject=$subject
					date=$now|date:'Y-m-d'
					deadline=null
					signing_date=$now|date:'Y-m-d'
					signing_place=$signing_place
					validation_date=null
					payment_date=null
					payment_comment=null
					items=$items
					total=$total
					siret=$siret
					org_contact=$org_contact
					recipient_business_name=$recipient_business_name
					recipient_address=$recipient_address
					recipient_member_id=$recipient_member_id
					recipient_member_number=$recipient_member_number
					introduction_text=null
					payment_text=null
					extra_text=null
					comment=$comment
					vat_exemption=$vat_exemption
					last_modification_date=$now|atom_date
					module_version=$VERSION
				}}
			{{/if}}

			{{:save id=$_GET.id|intval
				validate_schema="./schema/quotation.json"
				validate_only="status, child_id, parent_id, validation_date, last_modification_date"
				status=$new_status
				child_id=$invoice_id|intval
				parent_id=$_GET.id|intval
				validation_date=$validation_date
				last_modification_date=$now|atom_date
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
	
	{{:include file='./check_max_length.tpl' check_value=$_POST.payment_comment check_max=256 check_label='Remarque trop longue' keep='check_errors'}}

	{{:include file='./shim/array_last_num_key.tpl' keep='transaction_id, check_errors' array=$_POST.transaction name='transaction_id' error_message='Écriture séléctionnée invalide.'}}
	{{#transactions id=$transaction_id|intval}}{{:assign .='transaction'}}{{/transactions}}
	{{if $transaction_id && !$transaction}}
		{{:assign var='check_errors.' value="Transaction sélectionnée invalide."}}
	{{/if}}

	{{if !$check_errors}}
		{{:save id=$invoice.id|intval
			validate_schema="./schema/invoice.json"
			validate_only="status, transaction_id, payment_date, payment_comment, last_modification_date"
			status=$PAID_STATUS
			transaction_id=$transaction.id|intval
			payment_date=$date
			payment_comment=$_POST.payment_comment
			last_modification_date=$now|atom_date
		}}
		{{:http redirect="details.html?id=%d&ok=6&show=invoice"|args:$invoice.id}}
	{{/if}}

{{elseif $_POST.cancel_submit}}
	{{if !$_GET.id}}
		{{:assign var='check_errors.' value='Aucun document sélectionné.'}}
	{{/if}}
	{{:include file='./check_max_length.tpl' check_value=$_POST.reason check_max=256 check_label='Motif trop long' keep='check_errors'}}
	{{if !$check_errors}}
		{{#load id=$_GET.id}}
			{{if $status === $DRAFT_STATUS}}
				{{:assign var='check_errors.' value='Les brouillons ne peuvent pas être annulés. Vous pouvez néanmoins les supprimer.'}}
			{{elseif $cancelled}}
				{{:assign var='check_errors.' value='Le document est déjà annulé.'}}
			{{elseif $archived}}
				{{:assign link='<a href="action.html?id=%d&show=%s&action=ask_unarchiving">Sortir le document des archives</a>'|args:$id:$type}}
				{{:assign var='check_errors.' value='Un document archivé ne peut pas être annulé. %s.'|args:$link}}
			{{/if}}
			{{:assign var='allowed_type' from='DOCUMENT_TYPES.%s'|args:$type}}
			{{if !$allowed_type}}
				{{:assign var='check_errors.' value='Type invalide : %s!'|args:$type}}
			{{/if}}
			{{if !$check_errors}}
				{{:save id=$id|intval
					validate_schema="./schema/%s.json"|args:$type
					validate_only="cancelled, cancellation_reason, last_modification_date"
					cancelled=true
					cancellation_reason=$_POST.reason
					last_modification_date=$now|atom_date
				}}
				{{:http redirect="index.html?ok=5&show=%s"|args:$type}}
			{{/if}}
		{{/load}}
	{{/if}}

{{elseif $_POST.archive_submit || $_POST.unarchive_submit}}
	{{if !$_GET.id}}
		{{:assign var='check_errors.' value='Aucun document sélectionné.'}}
	{{/if}}
	{{if !$check_errors}}
		{{#load id=$_GET.id}}
			{{if $status === $DRAFT_STATUS}}
				{{:assign var='check_errors.' value='Les brouillons ne peuvent pas être archivés.'}}
			{{/if}}
			{{if $archived && $_POST.archive_submit}}
				{{:assign var='check_errors.' value='Le document est déjà archivé.'}}
			{{elseif !$archived && $_POST.unarchive_submit}}
				{{:assign var='check_errors.' value='Le document est déjà sorti des archives.'}}
			{{/if}}
			{{if $status === $AWAITING_STATUS}}
				{{if $type === $INVOICE_TYPE}}
					{{:assign var='check_errors.' value='Les factures doivent être soit payées soit annulées pour pouvoir être archivées.'}}
				{{else}}
					{{:assign var='check_errors.' value='Les devis doivent être soit validés ou soit refusés pour pouvoir être archivés.'}}
				{{/if}}
			{{/if}}
			{{:assign var='allowed_type' from='DOCUMENT_TYPES.%s'|args:$type}}
			{{if !$allowed_type}}
				{{:assign var='check_errors.' value='Type invalide : %s!'|args:$type}}
			{{/if}}
			{{if !$check_errors}}
				{{:save id=$id|intval
					validate_schema="./schema/%s.json"|args:$type
					validate_only="archived, last_modification_date"
					archived=$_POST.archive_submit|boolval
					last_modification_date=$now|atom_date
				}}
				{{if $_POST.archive_submit}}
					{{:http redirect="details.html?id=%d&ok=7&show=%s"|args:$id:$type}}
				{{else}}
					{{:http redirect="details.html?id=%d&ok=8&show=%s"|args:$id:$type}}
				{{/if}}
			{{/if}}
		{{/load}}
	{{/if}}

{{elseif $_POST.duplicate_submit}}
	{{if !$_GET.id}}
		{{:assign var='check_errors.' value='Aucun document sélectionné.'}}
	{{else}}
		{{#load id=$_GET.id}}
			{{:include file='./generate_next_key.tpl' assign_to='new_key' keep='new_key'}}
			{{if $type === $INVOICE_TYPE}}
				{{:save
					validate_schema="./schema/invoice.json"
					key=$new_key
					type=$type
					status=$DRAFT_STATUS
					cancelled=false
					cancellation_reason=null
					author_id=$author_id
					parent_id=$parent_id
					duplicated_from_id=$id|intval
					transaction_id=null
					subject=$subject
					date=$date
					deadline=$deadline
					signing_date=$signing_date
					signing_place=$signing_place
					validation_date=null
					payment_date=null
					payment_comment=null
					items=$items
					total=$total
					siret=$siret
					org_contact=$org_contact
					recipient_business_name=$recipient_business_name
					recipient_address=$recipient_address
					recipient_member_id=$recipient_member_id
					recipient_member_number=$recipient_member_number
					introduction_text=$introduction_text
					payment_text=$payment_text
					extra_text=$extra_text
					comment=$comment
					vat_exemption=$vat_exemption
					last_modification_date=$now|atom_date
					module_version=$VERSION
				}}
			{{else}}
				{{:save
					validate_schema="./schema/quotation.json"
					key=$new_key
					type=$type
					status=$DRAFT_STATUS
					cancelled=false
					cancellation_reason=null
					author_id=$author_id
					child_id=null
					parent_id=null
					duplicated_from_id=$id|intval
					subject=$subject
					date=$date
					deadline=$deadline
					signing_date=$signing_date
					signing_place=$signing_place
					validation_date=null
					items=$items
					total=$total
					siret=$siret
					org_contact=$org_contact
					recipient_business_name=$recipient_business_name
					recipient_address=$recipient_address
					recipient_member_id=$recipient_member_id
					recipient_member_number=$recipient_member_number
					introduction_text=$introduction_text
					payment_text=$payment_text
					extra_text=$extra_text
					comment=$comment
					vat_exemption=$vat_exemption
					last_modification_date=$now|atom_date
					module_version=$VERSION
				}}
			{{/if}}
			{{:http redirect="index.html?ok=4"}}
		{{/load}}
	{{/if}}
{{elseif $_POST.edit_comment_submit}}
	{{if !$_GET.id}}
		{{:assign var='check_errors.' value='Aucun document sélectionné.'}}
	{{/if}}
	{{:include file='./check_max_length.tpl' check_value=$_POST.comment check_max=1024 check_label='Remarques trop longues' keep='check_errors'}}
	{{if !$check_errors}}
		{{#load id=$_GET.id}}
			{{:assign var='allowed_type' from='DOCUMENT_TYPES.%s'|args:$type}}
			{{if !$allowed_type}}
				{{:assign var='check_errors.' value='Type invalide : %s!'|args:$type}}
			{{else}}
				{{:save id=$id|intval
					validate_schema="./schema/%s.json"|args:$type
					validate_only="comment, last_modification_date"
					comment=$_POST.comment
					last_modification_date=$now|atom_date
				}}
				{{:http redirect="index.html?ok=6&show=%s"|args:$type}}
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
			{{#load id=$_POST.id|intval}}
				{{:assign var='allowed_type' from='DOCUMENT_TYPES.%s'|args:$type}}
				{{if !$allowed_type}}
					{{:assign var='check_errors.' value='Type invalide : %s!'|args:$type}}
				{{else}}
					{{:save id=$id
						validate_schema="./schema/%s.json"|args:$type
						validate_only="status, cancelled, archived, last_modification_date"
						status=$_POST.status
						cancelled=$_POST.cancelled|boolval
						archived=$_POST.archived|boolval
						last_modification_date=$now|atom_date
					}}
					{{:http redirect="details.html?id=%d&ok=2&show=%s"|args:$id:$type}}
				{{/if}}
			{{/load}}
		{{/if}}
	{{/if}}
{{/if}}
{{/restrict}}
