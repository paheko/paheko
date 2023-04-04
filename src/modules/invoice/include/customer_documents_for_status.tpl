{{if $status}}
	{{:assign var="label" from="STATUS_LABELS.%s"|args:$status}}
{{else}}
	{{if $type === $INVOICE_TYPE}}
		{{:assign label=$INVOICE_CANCELLED_LABEL}}
	{{else}}
		{{:assign label=$QUOTATION_CANCELLED_LABEL}}
	{{/if}}
{{/if}}

<h4 class="infos">{{$label}}</h4>
{{#list
	select=$select
	where=$where
	order=$order
	:business_name=$_GET.id
	:type=$type
	:status=$status
}}
<tr>
	<td><a href="details.html?id={{$id|intval}}">{{$key}}</a></td>
	<td>{{$date|date_short}}</td>
	<td>{{$deadline|date_short}}</td>
	<td><a href="details.html?id={{$id|intval}}">{{$subject}}</a></td>
	<td class="money">{{$total|money_currency}}</td>
	<td class="num">
		{{if $recipient_member_id}}
			{{:link href="!users/details.php?id=%s"|args:$recipient_member_id label=$recipient_member_numero|or:$recipient_member_id}}
		{{else}}
		-
		{{/if}}
	</td>
	<td class="actions">
		{{:include file='./document_list_buttons.html'}}
	</td>
</tr>

{{else}}
	{{if $type === $INVOICE_TYPE}}
		{{:assign no_label='Aucune facture'}}
	{{else}}
		{{:assign no_label='Aucun devis'}}
	{{/if}}
	<p class="infos">{{$no_label}} pour le status "{{$label}}".</p>
{{/list}}
