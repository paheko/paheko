{{* ================= Summary ================= *}}

{{* Default values (only) for new documents *}}
{{if !$document}}
	{{:assign payment_text_default=$module.config.payment_text}}
	{{:assign extra_text_default=$module.config.extra_text}}
{{else}}
	{{:assign payment_text_default=null}}
	{{:assign extra_text_default=null}}
{{/if}}

<nav><ul class="breadcrumb"><li>Destinataire</li><li>Objet</li><li>Articles</li><li class="current">Récapitulatif</li></ul></nav>
<fieldset>
	<legend><h2>Destinataire</h2></legend>
	{{if $customer_id}}{{#users id=$customer_id|intval}}{{:assign .='recipient_member'}}{{/users}}{{/if}}
	<p class="infos">
		<strong class="business_name">{{if $_POST.recipient_business_name}}{{$_POST.recipient_business_name}}{{else}}{{$recipient_member.nom}}{{/if}}</strong><br />
		{{if $_POST.recipient_address}}{{$_POST.recipient_address|escape|nl2br}}{{else}}{{$recipient_member.address|escape|nl2br}}{{/if}}
	</p>
	{{if $_POST.recipient_business_name}}
		{{if $customer_id}}
			<p>
				Associé·e au membre {{if $recipient_member.number}}n°{{$recipient_member.number}}{{/if}} : "{{:link href="!users/details.php?id=%d"|args:$recipient_member.id label=$recipient_member.nom}}"
				{{if $_POST.recipient_business_name !== $recipient_member.nom}}, sous la désignation "{{$recipient_business_name}}".{{/if}}<br />
			</p>
		{{/if}}
	{{/if}}
</fieldset>

<fieldset>
	<legend><h2>Objet</h2></legend>
	<dl class="property_list">
		<dt>Intitulé :</dt>
		<dd>{{$_POST.subject}}</dd>
		<dt>Date :</dt>
		<dd>{{$_POST.date|date_short}}</dd>
		{{if $_POST.deadline}}
			<dt>Échéance :</dt>
			<dd>{{$_POST.deadline|date_short}}</dd>
		{{/if}}
		<dt>Référence :</dt>
		<dd>{{$_POST.key}}</dd>
		<dt>Total :</dt>
		<dd>{{$_POST.quotation_total|money_int|money_currency:false}}</dd>
		<dt>Contact :</dt>
		<dd>{{$_POST.org_contact}}</dd>
	</dl>
</fieldset>

<fieldset>
	<legend><h2>Articles</h2></legend>
	{{if $_POST.items}}
		<table id="item_list" class="list">
			<thead>
				<tr>
					<th>Réf.</th>
					<th>Dénomination</th>
					<th>Description</th>
					<th>Prix unitaire</th>
					<th>Quantité</th>
				</tr>
			</thead>
			<tbody>
			{{#foreach from=$_POST.items item='item'}}
				{{if $item.unit_price || $item.quantity}} {{* Ignore default empty line *}}
					<tr>
						<td>{{$item.reference}}</td>
						<td>{{$item.name}}</td>
						<td>{{$item.description|escape|nl2br}}</td>
						<td>{{$item.unit_price|money_int|money:false}}</td>
						<td>{{$item.quantity}}</td>
					</tr>
				{{/if}}
			{{/foreach}}
			</tbody>
		</table>
		<p>
			Total : {{$_POST.quotation_total|money_int|money_currency:false}}
		</p>
	{{else}}
		<p>Aucun article.</p>
	{{/if}}
</fieldset>

{{* ================= Validation ================= *}}

<form method="POST" action="{{"./edit.html?show=quotation&id=%d&step=final&last_step=footer"|args:$_GET.id}}">

	<fieldset>
		<legend><h2>Pied de page</h2></legend>
		<dl>
			{{:input type="textarea" name="payment_text" label="Instructions de paiement" source=$document default=$payment_text_default cols="50" rows="4"}}
			{{:input type="textarea" name="extra_text" label="Informations complémentaires" source=$document default=$extra_text_default cols="50" rows="2"}}
		</dl>
	{{if !$document}}<p>(La valeur pré-remplie de ces informations est modifiable dans la configuration du module.)</p>{{/if}}
	</fieldset>

	{{:assign var='customer_name' from='_POST.customer.%d'|args:$customer_id}}
	{{:input type="hidden" name="customer[%d]"|args:$customer_id default=$customer_name}}
	{{:input type="hidden" name="recipient_business_name" default=$_POST.recipient_business_name}}
	{{:input type="hidden" name="recipient_address" default=$_POST.recipient_address}}
	{{:input type="hidden" name="key" default=$_POST.key}}
	{{:input type="hidden" name="subject" default=$_POST.subject}}
	{{:input type="hidden" name="date" default=$_POST.date}}
	{{:input type="hidden" name="deadline" default=$_POST.deadline}}
	{{:input type="hidden" name="org_contact" default=$_POST.org_contact}}
	{{:input type="hidden" name="introduction_text" default=$_POST.introduction_text}}

	{{#foreach from=$_POST.items key='key' item='item'}}
		{{:assign var='reference' from='_POST.items.%s.reference'|args:$key}}
		{{:assign var='name' from='_POST.items.%s.name'|args:$key}}
		{{:assign var='description' from='_POST.items.%s.description'|args:$key}}
		{{:assign var='unit_price' from='_POST.items.%s.unit_price'|args:$key}}
		{{:assign var='quantity' from='_POST.items.%s.quantity'|args:$key}}
		
		{{:input type="hidden" name="items[%s][reference]"|args:$key default=$reference}}
		{{:input type="hidden" name="items[%s][name]"|args:$key default=$name}}
		{{:input type="hidden" name="items[%s][description]"|args:$key default=$description}}
		{{:input type="hidden" name="items[%s][unit_price]"|args:$key default=$unit_price}}
		{{:input type="hidden" name="items[%s][quantity]"|args:$key default=$quantity}}
	{{/foreach}}
	{{:input type="hidden" name="quotation_total" default=$_POST.quotation_total}}

	<p class="submit">
		{{:input type="hidden" name="quotation_submit" value="1"}}
		{{if !$document}}
			{{:assign button_label='Sauvegarder comme brouillon'}}
		{{else}}
			{{:assign button_label='Mettre à jour le brouillon'}}
		{{/if}}
		{{:button type="submit" name="ask_validation" label="Finaliser le devis" class="main" shape="right"}} ou bien
		{{:button type="submit" name="save_as_draft" label=$button_label shape="document"}}
	</p>

</form>
