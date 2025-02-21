{include file="_head.tpl" title="Fonctions avancées" current="config"}

{include file="config/_menu.tpl" current="advanced" sub_current=null}

{form_errors}

{if $_GET.msg == 'RESET'}
	<p class="block confirm">
		La remise à zéro a été effectuée. Une sauvegarde a également été créée.</p>
	</p>
{/if}

<p class="help">
	Ces fonctionnalités sont réservées à un public averti.
</p>

<dl class="large">
	<dt>Journal d'audit</dt>
	<dd class="help">
		Affiche l'historique des actions (connexion, changement de mot de passe, création de membre, modification comptable, etc.) effectuées par tous les membres.
	</dd>
	<dd>
		{linkbutton shape="history" label="Voir le journal d'audit" href="audit.php"}
	</dd>

	<dt>Accès à l'API</dt>
	<dd class="help">
		Permet de gérer les identifiants d'accès à l'API. Pour interfacer d'autres programmes et scripts avec les données de votre association.
	</dd>
	<dd>
		{linkbutton shape="settings" label="Gérer les accès à l'API" href="api.php"}
	</dd>

	<dt>SQL — Accès à la base de données brute</dt>
	<dd class="help">
		Visualiser le schéma des tables et les données brutes de la base de données, ou y effectuer des requêtes SQL.
	</dd>
	<dd>
		{linkbutton shape="code" label="Visualiser la base de données SQL" href="sql.php"}
	</dd>

	{if ENABLE_TECH_DETAILS}
		<dt>Journal des erreurs système</dt>
		<dd class="help">
			Affiche le détail des erreurs système de Paheko et PHP.
		</dd>
		<dd>
			{linkbutton shape="menu" label="Voir le journal des erreurs système" href="errors.php"}
		</dd>

		{if SQL_DEBUG}
			<dt>Journal des requêtes SQL</dt>
			<dd class="help">
				Affiche le détail de toutes les requêtes SQL exécutées et leurs performances.
			</dd>
			<dd>
				{linkbutton shape="menu" label="Voir le journal des requêtes SQL" href="sql_debug.php"}
			</dd>
		{/if}

	{/if}

	{if $logged_user.password}
	<dt>Remise à zéro</dt>
	<dd class="help">
		Efface toutes les données, sauf votre compte de membre. Utile pour revenir à l'état initial après une période d'essai.
	</dd>
	<dd>
		{linkbutton shape="delete" href="reset.php" label="Remise à zéro"}
	</dd>
	{/if}
</dl>


{include file="_foot.tpl"}