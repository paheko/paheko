{{:assign VERSION='0.2'}}

{{:assign var='vat_exemption_type.nonprofit' value='Non-lucratif'}}
{{:assign var='vat_exemption_type.profit' value='Lucratif'}}

{{:assign var='type_labels' quotation='Devis' invoice='Facture'}}
{{:assign var='status_labels' draft="Brouillon" awaiting='En attente de validation' rejected='Rejeté' validated='Validé' misc='Autre'}}

{{* Used in <select> *}}
{{:assign var='status_options[draft]' value=$status_labels.draft}}
{{:assign var='status_options[awaiting]' value=$status_labels.awaiting}}
{{:assign var='status_options[rejected]' value=$status_labels.rejected}}
{{:assign var='status_options[validated]' value=$status_labels.validated}}
{{:assign var='status_options[misc]' value=$status_labels.misc}}