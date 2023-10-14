-- Replace old placeholders
UPDATE services_reminders SET body = REPLACE(body, '#IDENTITE', '{{$identity}}'),
	subject = REPLACE(subject, '#IDENTITE', '');
UPDATE services_reminders SET body = REPLACE(body, '#NB_JOURS', '{{$nb_days}}'),
	subject = REPLACE(subject, '#NB_JOURS', '');
UPDATE services_reminders SET body = REPLACE(body, '#DELAI', '{{$delay}}'),
	subject = REPLACE(subject, '#DELAI', '');
UPDATE services_reminders SET body = REPLACE(body, '#DATE_RAPPEL', '{{$reminder_date}}'),
	subject = REPLACE(subject, '#DATE_RAPPEL', '');
UPDATE services_reminders SET body = REPLACE(body, '#DATE_EXPIRATION', '{{$expiry_date}}'),
	subject = REPLACE(subject, '#DATE_EXPIRATION', '');

UPDATE services_reminders SET body = REPLACE(body, '#NOM_ASSO', '{{$config.org_name}}'),
	subject = REPLACE(subject, '#NOM_ASSO', (SELECT value FROM config WHERE key = 'org_name'));
UPDATE services_reminders SET body = REPLACE(body, '#ADRESSE_ASSO', '{{$config.org_address}}'),
	subject = REPLACE(subject, '#ADRESSE_ASSO', '');
UPDATE services_reminders SET body = REPLACE(body, '#EMAIL_ASSO', '{{$config.org_email}}'),
	subject = REPLACE(subject, '#EMAIL_ASSO', '');
UPDATE services_reminders SET body = REPLACE(body, '#SITE_ASSO', '{{$config.org_web}}'),
	subject = REPLACE(subject, '#SITE_ASSO', '');
UPDATE services_reminders SET body = REPLACE(body, '#URL_RACINE', '{{$root_url}}'),
	subject = REPLACE(subject, '#URL_RACINE', '');
UPDATE services_reminders SET body = REPLACE(body, '#URL_SITE', '{{$site_url}}'),
	subject = REPLACE(subject, '#URL_SITE', '');
UPDATE services_reminders SET body = REPLACE(body, '#URL_ADMIN', '{{$admin_url}}'),
	subject = REPLACE(subject, '#URL_ADMIN', '');