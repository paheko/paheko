{{:assign VERSION='0.2'}}
{{:assign DRAFT_STATUS='draft' AWAITING_STATUS='awaiting' REJECTED_STATUS='rejected' VALIDATED_STATUS='validated' MISC_STATUS='misc'}}
{{:assign QUOTATION_TYPE='quotation' INVOICE_TYPE='invoice'}}
{{:assign NONPROFIT_VAT_EXEMPTION_TYPE='nonprofit' PROFIT_VAT_EXEMPTION_TYPE='profit'}}

{{* Labels *}}
{{:assign var='TYPE_LABELS.%s'|args:$QUOTATION_TYPE value='Devis'}}
{{:assign var='TYPE_LABELS.%s'|args:$INVOICE_TYPE value='Facture'}}

{{:assign var='STATUS_LABELS.%s'|args:$DRAFT_STATUS value='Brouillon'}}
{{:assign var='STATUS_LABELS.%s'|args:$AWAITING_STATUS value='En attente de validation'}}
{{:assign var='STATUS_LABELS.%s'|args:$REJECTED_STATUS value='Rejeté'}}
{{:assign var='STATUS_LABELS.%s'|args:$VALIDATED_STATUS value='Validé'}}
{{:assign var='STATUS_LABELS.%s'|args:$MISC_STATUS value='Autre'}}

{{:assign var='INVOICE_STATUS_LABELS.%s'|args:$DRAFT_STATUS value='Brouillon'}}
{{:assign var='INVOICE_STATUS_LABELS.%s'|args:$AWAITING_STATUS value='En attente de paiement'}}
{{:assign var='INVOICE_STATUS_LABELS.%s'|args:$REJECTED_STATUS value='Rejeté'}}
{{:assign var='INVOICE_STATUS_LABELS.%s'|args:$VALIDATED_STATUS value='Validé'}}
{{:assign var='INVOICE_STATUS_LABELS.%s'|args:$MISC_STATUS value='Autre'}}

{{:assign var='VAT_EXEMPTION_TYPE_LABELS.%s'|args:$NONPROFIT_VAT_EXEMPTION_TYPE value='Non-lucratif'}}
{{:assign var='VAT_EXEMPTION_TYPE_LABELS.%s'|args:$PROFIT_VAT_EXEMPTION_TYPE value='Lucratif'}}
