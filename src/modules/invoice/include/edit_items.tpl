<nav><ul class="breadcrumb"><li>Destinataire</li><li>Objet</li><li class="current">Articles</li></ul></nav>

<form method="POST" action="{{"./edit.html?show=quotation&id=%d&step=items&last_step=main"|args:$_GET.id}}">

	{{:assign items=$document.items}}
	{{:include file='./item_list.tpl'}}

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

	<p class="submit">
		{{:button type="submit" name="quotation_submit" label="Continuer" class="main" shape="right"}}
		{{if !$items}}{{:button type="submit" name="quotation_submit" label="Continuer sans ajouter d'articles" shape="right"}}{{/if}}
	</p>

</form>
