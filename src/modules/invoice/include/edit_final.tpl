<nav><ul class="breadcrumb"><li>Destinataire</li><li>Objet</li><li>Articles</li><li>Récapitulatif</li><li class="current">Finalisation</li></ul></nav>

<p class="infos">Pour finaliser le devis, vous devez choisir la date et le lieu de signature.</p>

<form method="POST" action="{{"./edit.html?show=quotation&id=%d&step=final&last_step=validation"|args:$_GET.id}}">

	<fieldset>
		<legend><h2>Signature du devis</h2></legend>
		<div id="signing_inputs">
			<dl>
				{{:input type="text" name="signing_place" label="Lieu de la signature" placeholder="ex : Dijon" source=$document default=$module.config.signing_place}}
				{{:input type="date" name="signing_date" label="Date de la signature" source=$document default=$now}}
			</dl>
		</div>
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

	{{:input type="hidden" name="payment_text" default=$_POST.payment_text}}
	{{:input type="hidden" name="extra_text" default=$_POST.extra_text}}
	
	{{:input type="hidden" name="signing" default="1"}}

	<p class="submit">
		{{:button type="submit" name="quotation_submit" label="Finaliser le devis" class="main"}}
	</p>
</form>
