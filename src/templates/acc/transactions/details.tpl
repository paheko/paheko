{include file="_head.tpl" title="Écriture n°%d"|args:$transaction.id current="acc" prefer_landscape=true}


{if isset($_GET['created'])}
	<p class="block confirm">
		L'écriture a bien été créée.
	</p>
{/if}

<nav class="tabs">
	<aside>
		{if !$transaction.hash && $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN) && $transaction_year->isOpen()}
			{linkbutton href="lock.php?id=%d"|args:$transaction.id shape="lock" label="Verrouiller" target="_dialog"}
		{/if}
{if PDF_COMMAND}
		{linkbutton href="?id=%d&_pdf"|args:$transaction.id shape="download" label="Télécharger en PDF"}
{/if}
	</aside>
	<nav>
	{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN) && !$transaction->isLocked() && $transaction_year->isOpen()}
		{linkbutton href="edit.php?id=%d"|args:$transaction.id shape="edit" label="Modifier cette écriture" accesskey="M"}
		{linkbutton href="delete.php?id=%d"|args:$transaction.id shape="delete" label="Supprimer cette écriture" accesskey="S" target="_dialog"}
	{/if}
	{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE)}
		{linkbutton href="new.php?copy=%d"|args:$transaction.id shape="plus" label="Dupliquer cette écriture" accesskey="D"}
	{/if}
	</nav>
</nav>

<header class="summary print-only">
	{if $config.files.logo}
	<figure class="logo print-only"><img src="{$config->fileURL('logo', '150px')}" alt="" /></figure>
	{/if}
	<h2>{$config.org_name}</h2>
	<h3>{"Écriture n°%d"|args:$transaction.id}</h3>
</header>

{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE) && $transaction->isWaiting()}
<div class="block alert">
	<form method="post" action="{$self_url}">
	{if $transaction.type == $transaction::TYPE_DEBT}
		<h3>Dette en attente</h3>
		{linkbutton shape="check" label="Régler cette dette" href="!acc/transactions/new.php?payoff=%d"|args:$transaction.id}
	{else}
		<h3>Créance en attente</h3>
		{linkbutton shape="export" label="Régler cette créance" href="!acc/transactions/new.php?payoff=%d"|args:$transaction.id}
	{/if}
		{button type="submit" shape="check" label="Marquer manuellement comme réglée" name="mark_paid" value="1"}
		{csrf_field key=$csrf_key}
	</form>
</div>
{/if}

{if $transaction.status & $transaction::STATUS_ERROR}
<div class="error block">
	<p>Cette écriture est erronée suite à un bug. Il est conseillé de la modifier pour remettre les comptes manquants, ou la supprimer et la re-créer.
	Voir <a href="https://fossil.kd2.org/paheko/wiki?name=Changelog#1_0_1" target="_blank">cette page pour plus d'explications</a></p>
	<p>Les lignes erronées sont affichées en bas de cette page.</p>
	<p><em>(Ce message disparaîtra si vous modifiez l'écriture pour la corriger.)</em></p>
</div>
{/if}

<section class="transaction-details-container">
	<article>
		<dl class="describe">
			<dt>Libellé</dt>
			<dd><h2>{$transaction.label|escape|linkify_transactions}</h2></dd>
			<dt>Type</dt>
			<dd>
				{$transaction->getTypeName()}
			</dd>
		{if $transaction.hash}
			<dt>Verrou</dt>
			<dd><span class="alert">{icon shape="lock"} Écriture verrouillée</span></dd>
		{/if}

			{if $transaction.type == $transaction::TYPE_DEBT || $transaction.type == $transaction::TYPE_CREDIT}
				<dt>Statut</dt>
				<dd>
					{if $transaction->isPaid()}
						<form method="post" action="">
						<span class="confirm">{icon shape="check"}</span> Réglée
						{button type="submit" label="Marquer comme en attente de paiement" name="mark_waiting" value="1"}
						{csrf_field key=$csrf_key}
					</form>

					{elseif $transaction->isWaiting()}
						<span class="alert">{icon shape="alert"}</span> En attente de règlement
					{/if}
				</dd>
			{/if}

			<dt>Date</dt>
			<dd>{$transaction.date|date:'l j F Y (d/m/Y)'}</dd>

			<dt>Exercice</dt>
			<dd>
				<strong>{link href="!acc/reports/ledger.php?year=%d"|args:$transaction.id_year label=$transaction_year.label}</strong>
				— Du {$transaction_year.start_date|date_short} au {$transaction_year.end_date|date_short}
				{tag preset=$transaction_year->getStatusTagPreset()}
				</small>
			</dd>

			<dt>Numéro pièce comptable</dt>
			<dd>{if $transaction.reference}<mark>{$transaction.reference}</mark>{else}—{/if}</dd>

			{if $transaction.type != $transaction::TYPE_ADVANCED}
				<dt>Référence de paiement</dt>
				<dd>{if $ref = $transaction->getPaymentReference()}<mark>{$ref}</mark>{else}—{/if}</dd>
				<dt>Projet</dt>
				<dd>
				{if $project = $transaction->getProject()}
					<mark class="variant-a">{link href="!acc/reports/statement.php?project=%d&year=%d"|args:$project.id:$transaction.id_year label=$project.name}</mark>
				{else}
					—
				{/if}
			{/if}

			<dt>Remarques</dt>
			<dd>{if $transaction.notes}{$transaction.notes|escape|nl2br|linkify_transactions}{else}—{/if}</dd>


			{if $transaction.id_creator}
				<dt>Écriture créée par</dt>
				<dd>
					{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ)}
						<a href="{$admin_url}users/details.php?id={$transaction.id_creator}">{$creator_name}</a>
					{else}
						{$creator_name}
					{/if}
				</dd>
			{/if}

		</dl>

		<nav class="tabs">
			{if $transaction.type != $transaction::TYPE_ADVANCED}
			<ul class="small transaction-details-toggle">
				<li{if $simple} class="current"{/if}>{link href="?id=%d&advanced=0"|args:$transaction.id label="Vue simplifiée"}</li>
				<li{if !$simple} class="current"{/if}>{link href="?id=%d&advanced=1"|args:$transaction.id label="Vue comptable"}</li>
			</ul>
			{/if}
		</nav>

		{if $transaction.type != $transaction::TYPE_ADVANCED}
			<div class="transaction-details{if !$simple} hidden{/if}">
				<div class="amount">
					<h3>{$transaction->getTypeName()}</h3>
					<span>
						{$transaction->getLinesCreditSum()|abs|escape|money_currency}
					</span>
				</div>
				<div class="account">
					<h4>{$details.left.label}</h4>
					<h3>{link href="!acc/accounts/journal.php?id=%d"|args:$details.left.id label=$details.left.name}</h3>
					{*<h5>({if $details.left.direction == 'credit'}Crédit{else}Débit{/if})</h5>*}
				</div>
				{if $transaction.type == $transaction::TYPE_TRANSFER}
					<div class="amount"><span>{icon shape="right"}</span></div>
				{/if}
				<div class="account">
					<h4>{$details.right.label}</h4>
					<h3>{link href="!acc/accounts/journal.php?id=%d&year=%d"|args:$details.right.id,$transaction.id_year label=$details.right.name}</h3>
					{*<h5>({if $details.right.direction == 'credit'}Crédit{else}Débit{/if})</h5>*}
				</div>
			</div>
		{/if}

		<div class="transaction-details-advanced{if $simple && $transaction.type !== $transaction::TYPE_ADVANCED} hidden{/if}">
			<table class="list">
				<thead>
					<tr>
						<td class="num">N° compte</td>
						<th>Compte</th>
						<td class="money">Débit</td>
						<td class="money">Crédit</td>
						<td>Libellé ligne</td>
						<td>Référence ligne</td>
						<td>Projet</td>
					</tr>
				</thead>
				<tbody>
					{foreach from=$transaction->getLinesWithAccounts() item="line"}
					<tr>
						<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$line.id_account}&amp;year={$transaction.id_year}">{$line.account_code}</a></td>
						<td>{$line.account_label}</td>
						<td class="money">{if $line.debit}{$line.debit|escape|money}{/if}</td>
						<td class="money">{if $line.credit}{$line.credit|escape|money}{/if}</td>
						<td>{$line.label}</td>
						<td>{$line.reference}</td>
						<td>
							{if $line.id_project}
								{link href="!acc/reports/statement.php?project=%d&year=%d"|args:$line.id_project:$transaction.id_year label=$line.project_name}
							{/if}
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		{if $files_edit || count($files)}
		<div class="attachments noprint">
			<h3 class="ruler">Fichiers joints</h3>
			{include file="common/files/_context_list.tpl" files=$files edit=$files_edit path=$file_parent}
		</div>
		{/if}
	</article>

	<aside>

	{if count($linked_users)}
		<table class="list">
			<caption>Membres liés</caption>
			<tbody>
			{foreach from=$linked_users item="u"}
				<tr>
					<td class="num"><a href="{$admin_url}users/details.php?id={$u.id}">{$u.number}</a></td>
					<td>{$u.identity}</td>
					<td class="actions">{linkbutton href="!users/details.php?id=%d"|args:$u.id label="Fiche membre" shape="user"}</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	{/if}

	{if count($linked_subscriptions)}
		<table class="list">
			<caption>Inscriptions liées</caption>
			<tbody>
			{foreach from=$linked_subscriptions item="s"}
				<tr>
					<td class="num">{link href="!users/details.php?id=%d"|args:$s.id_user label=$s.user_number}</td>
					<td>{$s.user_identity}</td>
					<td class="actions">{linkbutton href="!services/user/?id=%d&only=%s"|args:$s.id_user:$s.id_subscription label="Inscription" shape="eye"}</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	{/if}

	{if count($linked_transactions)}
		<?php $amount = 0; ?>
		<table class="list">
			<caption>Écritures liées</caption>
			<tbody>
			{foreach from=$linked_transactions item="linked"}
				<?php $amount += $linked->sum(); ?>
				<tr>
					<td class="num"><a href="?id={$linked.id}" class="num">#{$linked.id}</a></td>
					<td>{$linked.label}</td>
					<td>{$linked.date|date_short}</td>
					<td class="money">{$linked->sum()|money_currency|raw}</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	{/if}
	</aside>

</section>

{literal}
<script type="text/javascript">
var list = $('.transaction-details-toggle li a');

if (list.length == 2) {
	var a = list[0];
	var b = list[1];

	a.onclick = () => {
		g.toggle('.transaction-details', true);
		g.toggle('.transaction-details-advanced', false);
		a.parentNode.classList.add('current');
		b.parentNode.classList.remove('current');
		return false;
	};
	b.onclick = () => {
		g.toggle('.transaction-details', false);
		g.toggle('.transaction-details-advanced', true);
		b.parentNode.classList.add('current');
		a.parentNode.classList.remove('current');
		return false;
	};
}
</script>
{/literal}

{$snippets|raw}

{include file="_foot.tpl"}