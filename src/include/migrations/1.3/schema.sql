---
--- Main stuff
---

CREATE TABLE IF NOT EXISTS config (
-- Configuration, key/value store
	key TEXT PRIMARY KEY NOT NULL,
	value TEXT NULL
);

CREATE TABLE IF NOT EXISTS config_users_fields (
	id INTEGER NOT NULL PRIMARY KEY,
	name TEXT NOT NULL,
	sort_order INTEGER NOT NULL,
	type TEXT NOT NULL,
	label TEXT NOT NULL,
	help TEXT NULL,
	required INTEGER NOT NULL DEFAULT 0,
	user_access_level INTEGER NOT NULL DEFAULT 0,
	management_access_level INTEGER NOT NULL DEFAULT 1,
	list_table INTEGER NOT NULL DEFAULT 0,
	options TEXT NULL,
	default_value TEXT NULL,
	sql TEXT NULL,
	system TEXT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS config_users_fields_name ON config_users_fields (name);

CREATE TABLE IF NOT EXISTS plugins
(
	id INTEGER NOT NULL PRIMARY KEY,
	name TEXT NOT NULL,
	label TEXT NOT NULL,
	description TEXT NULL,
	author TEXT NULL,
	author_url TEXT NULL,
	version TEXT NOT NULL,
	menu INT NOT NULL DEFAULT 0,
	home_button INT NOT NULL DEFAULT 0,
	restrict_section TEXT NULL,
	restrict_level INT NULL,
	config TEXT NULL,
	enabled INTEGER NOT NULL DEFAULT 0
);

CREATE UNIQUE INDEX IF NOT EXISTS plugins_name ON plugins (name);
CREATE INDEX IF NOT EXISTS plugins_menu ON plugins(menu, enabled);

CREATE TABLE IF NOT EXISTS plugins_signals
-- Link between plugins and signals
(
	signal TEXT NOT NULL,
	plugin TEXT NOT NULL REFERENCES plugins (name),
	callback TEXT NOT NULL,
	PRIMARY KEY (signal, plugin)
);

CREATE TABLE IF NOT EXISTS modules
-- List of modules
(
	id INTEGER NOT NULL PRIMARY KEY,
	name TEXT NOT NULL,
	label TEXT NOT NULL,
	description TEXT NULL,
	author TEXT NULL,
	author_url TEXT NULL,
	menu INT NOT NULL DEFAULT 0,
	home_button INT NOT NULL DEFAULT 0,
	restrict_section TEXT NULL,
	restrict_level INT NULL,
	config TEXT NULL,
	enabled INTEGER NOT NULL DEFAULT 0,
	web INTEGER NOT NULL DEFAULT 0,
	system INTEGER NOT NULL DEFAULT 0
);

CREATE UNIQUE INDEX IF NOT EXISTS modules_name ON modules (name);
CREATE INDEX IF NOT EXISTS modules_menu ON modules(menu, enabled);

CREATE TABLE IF NOT EXISTS modules_templates
-- List of forms special templates
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_module INTEGER NOT NULL REFERENCES modules (id) ON DELETE CASCADE,
	name TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS modules_templates_name ON modules_templates (id_module, name);

CREATE TABLE IF NOT EXISTS api_credentials
(
	id INTEGER NOT NULL PRIMARY KEY,
	label TEXT NOT NULL,
	key TEXT NOT NULL,
	secret TEXT NOT NULL,
	created TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	last_use TEXT NULL,
	access_level INT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS api_credentials_key ON api_credentials (key);

CREATE TABLE IF NOT EXISTS searches
-- Saved searches
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_user INTEGER NULL REFERENCES users (id) ON DELETE CASCADE, -- If not NULL, then search will only be visible by this user
	label TEXT NOT NULL,
	description TEXT NULL,
	updated TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(updated) IS NOT NULL AND datetime(updated) = updated),
	target TEXT NOT NULL, -- "users" ou "accounting"
	type TEXT NOT NULL, -- "json" ou "sql"
	content TEXT NOT NULL
);


CREATE TABLE IF NOT EXISTS compromised_passwords_cache
-- Cache des hash de mots de passe compromis
(
	hash TEXT NOT NULL PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS compromised_passwords_cache_ranges
-- Cache des préfixes de mots de passe compromis
(
	prefix TEXT NOT NULL PRIMARY KEY,
	date INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS emails (
-- List of emails addresses
-- We are not storing actual email addresses here for privacy reasons
-- So that we can keep the record (for opt-out reasons) even when the
-- email address has been removed from the users table
	id INTEGER NOT NULL PRIMARY KEY,
	hash TEXT NOT NULL,
	verified INTEGER NOT NULL DEFAULT 0,
	optout INTEGER NOT NULL DEFAULT 0,
	invalid INTEGER NOT NULL DEFAULT 0,
	fail_count INTEGER NOT NULL DEFAULT 0,
	sent_count INTEGER NOT NULL DEFAULT 0,
	fail_log TEXT NULL,
	last_sent TEXT NULL,
	added TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS emails_hash ON emails (hash);

CREATE TABLE IF NOT EXISTS emails_queue (
-- List of emails waiting to be sent
	id INTEGER NOT NULL PRIMARY KEY,
	sender TEXT NULL,
	recipient TEXT NOT NULL,
	recipient_hash TEXT NOT NULL,
	recipient_pgp_key TEXT NULL,
	subject TEXT NOT NULL,
	content TEXT NOT NULL,
	content_html TEXT NULL,
	sending INTEGER NOT NULL DEFAULT 0, -- Will be changed to 1 when the queue run will start
	sending_started TEXT NULL, -- Will be filled with the datetime when the email sending was started
	context INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS emails_queue_attachments (
	id INTEGER NOT NULL PRIMARY KEY,
	id_queue INTEGER NOT NULL REFERENCES emails_queue (id) ON DELETE CASCADE,
	path TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS mailings (
	id INTEGER NOT NULL PRIMARY KEY,
	subject TEXT NOT NULL,
	body TEXT NULL,
	sender_name TEXT NULL,
	sender_email TEXT NULL,
	sent TEXT NULL CHECK (datetime(sent) IS NULL OR datetime(sent) = sent),
	anonymous INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS mailings_sent ON mailings (sent);

CREATE TABLE IF NOT EXISTS mailings_recipients (
	id INTEGER NOT NULL PRIMARY KEY,
	id_mailing INTEGER NOT NULL REFERENCES mailings (id) ON DELETE CASCADE,
	email TEXT NULL,
	id_email TEXT NULL REFERENCES emails (id) ON DELETE CASCADE,
	extra_data TEXT NULL
);

CREATE INDEX IF NOT EXISTS mailings_recipients_id ON mailings_recipients (id);

---
--- Users
---

-- CREATE TABLE users (...);
-- Organization users table, dynamically created, see config_users_fields table

CREATE TABLE IF NOT EXISTS users_categories
-- Users categories, mainly used to manage rights
(
	id INTEGER PRIMARY KEY NOT NULL,
	name TEXT NOT NULL,

	-- Permissions, 0 = no access, 1 = read-only, 2 = read-write, 9 = admin
	perm_web INTEGER NOT NULL DEFAULT 1,
	perm_documents INTEGER NOT NULL DEFAULT 1,
	perm_users INTEGER NOT NULL DEFAULT 1,
	perm_accounting INTEGER NOT NULL DEFAULT 1,

	perm_subscribe INTEGER NOT NULL DEFAULT 0,
	perm_connect INTEGER NOT NULL DEFAULT 1,
	perm_config INTEGER NOT NULL DEFAULT 0,

	hidden INTEGER NOT NULL DEFAULT 0,
	allow_passwordless_login INTEGER NOT NULL DEFAULT 0,
	force_otp INTEGER NOT NULL DEFAULT 0,
	force_pgp INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS users_categories_hidden ON users_categories (hidden);
CREATE INDEX IF NOT EXISTS users_categories_name ON users_categories (name);
CREATE INDEX IF NOT EXISTS users_categories_hidden_name ON users_categories (hidden, name);

CREATE TABLE IF NOT EXISTS users_sessions
-- Permanent sessions for logged-in users
(
	selector TEXT NOT NULL PRIMARY KEY,
	hash TEXT NOT NULL,
	id_user INTEGER NULL REFERENCES users (id) ON DELETE CASCADE,
	expiry INT NOT NULL
);

CREATE TABLE IF NOT EXISTS logs
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_user INTEGER NULL REFERENCES users (id) ON DELETE CASCADE,
	type INTEGER NOT NULL,
	details TEXT NULL,
	created TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(created) IS NOT NULL AND datetime(created) = created),
	ip_address TEXT NULL
);

CREATE INDEX IF NOT EXISTS logs_ip ON logs (ip_address, type, created);
CREATE INDEX IF NOT EXISTS logs_user ON logs (id_user, type, created);
CREATE INDEX IF NOT EXISTS logs_created ON logs (created);

---
--- Services
---

CREATE TABLE IF NOT EXISTS services
-- Services types (French: cotisations)
(
	id INTEGER PRIMARY KEY NOT NULL,

	label TEXT NOT NULL,
	description TEXT NULL,

	duration INTEGER NULL CHECK (duration IS NULL OR duration > 0), -- En jours
	start_date TEXT NULL CHECK (start_date IS NULL OR date(start_date) = start_date),
	end_date TEXT NULL CHECK (end_date IS NULL OR (date(end_date) = end_date AND date(end_date) >= date(start_date)))
);

CREATE TABLE IF NOT EXISTS services_fees
-- Services fees
(
	id INTEGER PRIMARY KEY NOT NULL,

	label TEXT NOT NULL,
	description TEXT NULL,

	amount INTEGER NULL,
	formula TEXT NULL, -- Formula to calculate fee amount dynamically (this contains a SQL statement)

	id_service INTEGER NOT NULL REFERENCES services (id) ON DELETE CASCADE,
	id_account INTEGER NULL REFERENCES acc_accounts (id) ON DELETE SET NULL CHECK (id_account IS NULL OR id_year IS NOT NULL), -- NULL if fee is not linked to accounting, this is reset using a trigger if the year is deleted
	id_year INTEGER NULL REFERENCES acc_years (id) ON DELETE SET NULL, -- NULL if fee is not linked to accounting
	id_project INTEGER NULL REFERENCES acc_projects (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS services_users
-- Records of services and fees linked to users
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_user INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
	id_service INTEGER NOT NULL REFERENCES services (id) ON DELETE CASCADE,
	id_fee INTEGER NULL REFERENCES services_fees (id) ON DELETE CASCADE, -- This can be NULL if there is no fee for the service

	paid INTEGER NOT NULL DEFAULT 0,
	expected_amount INTEGER NULL,

	date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(date) IS NOT NULL AND date(date) = date),
	expiry_date TEXT NULL CHECK (date(expiry_date) IS NULL OR date(expiry_date) = expiry_date)
);

CREATE UNIQUE INDEX IF NOT EXISTS su_unique ON services_users (id_user, id_service, id_fee, date);

CREATE INDEX IF NOT EXISTS su_service ON services_users (id_service);
CREATE INDEX IF NOT EXISTS su_fee ON services_users (id_fee);
CREATE INDEX IF NOT EXISTS su_paid ON services_users (paid);
CREATE INDEX IF NOT EXISTS su_expiry ON services_users (expiry_date);

CREATE TABLE IF NOT EXISTS services_reminders
-- Reminders for service expiry
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_service INTEGER NOT NULL REFERENCES services (id) ON DELETE CASCADE,

	delay INTEGER NOT NULL, -- Delay in days before or after expiry date

	subject TEXT NOT NULL,
	body TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS services_reminders_sent
-- Records of sent reminders, to keep track
(
	id INTEGER NOT NULL PRIMARY KEY,

	id_user INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
	id_service INTEGER NOT NULL REFERENCES services (id) ON DELETE CASCADE,
	id_reminder INTEGER NOT NULL REFERENCES services_reminders (id) ON DELETE CASCADE,

	sent_date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(sent_date) IS NOT NULL AND date(sent_date) = sent_date),
	due_date TEXT NOT NULL CHECK (date(due_date) IS NOT NULL AND date(due_date) = due_date)
);

CREATE UNIQUE INDEX IF NOT EXISTS srs_index ON services_reminders_sent (id_user, id_service, id_reminder, due_date);

CREATE INDEX IF NOT EXISTS srs_reminder ON services_reminders_sent (id_reminder);
CREATE INDEX IF NOT EXISTS srs_user ON services_reminders_sent (id_user);

--
-- Accounting
--

CREATE TABLE IF NOT EXISTS acc_charts
-- Accounting charts (plans comptables)
(
	id INTEGER NOT NULL PRIMARY KEY,
	country TEXT NULL,
	code TEXT NULL, -- the code is NULL if the chart is user-created or imported
	label TEXT NOT NULL,
	archived INTEGER NOT NULL DEFAULT 0 -- 1 = archived, cannot be changed
);

CREATE TABLE IF NOT EXISTS acc_accounts
-- Accounts of the charts (comptes)
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_chart INTEGER NOT NULL REFERENCES acc_charts (id) ON DELETE CASCADE,

	code TEXT NOT NULL, -- can contain numbers and letters, eg. 53A, 53B...

	label TEXT NOT NULL,
	description TEXT NULL,

	position INTEGER NOT NULL, -- position in the balance sheet (position actif/passif/charge/produit)
	type INTEGER NOT NULL DEFAULT 0, -- type (category) of favourite account: bank, cash, third party, etc.
	user INTEGER NOT NULL DEFAULT 1, -- 0 = is part of the original chart, 0 = has been added by the user
	bookmark INTEGER NOT NULL DEFAULT 0 -- 1 = is marked as favorite
);

CREATE UNIQUE INDEX IF NOT EXISTS acc_accounts_codes ON acc_accounts (code, id_chart);
CREATE INDEX IF NOT EXISTS acc_accounts_type ON acc_accounts (type);
CREATE INDEX IF NOT EXISTS acc_accounts_position ON acc_accounts (position);
CREATE INDEX IF NOT EXISTS acc_accounts_bookmarks ON acc_accounts (id_chart, bookmark, code);

-- Balance des comptes par exercice
CREATE VIEW IF NOT EXISTS acc_accounts_balances
AS
	SELECT id_year, id, label, code, type, debit, credit, bookmark,
		CASE -- 3 = dynamic asset or liability depending on balance
			WHEN position = 3 AND (debit - credit) > 0 THEN 1 -- 1 = Asset (actif) comptes fournisseurs, tiers créditeurs
			WHEN position = 3 THEN 2 -- 2 = Liability (passif), comptes clients, tiers débiteurs
			ELSE position
		END AS position,
		CASE
			WHEN position IN (1, 4) -- 1 = asset, 4 = expense
				OR (position = 3 AND (debit - credit) > 0)
			THEN
				debit - credit
			ELSE
				credit - debit
		END AS balance,
		CASE WHEN debit - credit > 0 THEN 1 ELSE 0 END AS is_debt
	FROM (
		SELECT t.id_year, a.id, a.label, a.code, a.position, a.type, a.bookmark,
			SUM(l.credit) AS credit,
			SUM(l.debit) AS debit
		FROM acc_accounts a
		INNER JOIN acc_transactions_lines l ON l.id_account = a.id
		INNER JOIN acc_transactions t ON t.id = l.id_transaction
		GROUP BY t.id_year, a.id
	);

CREATE TABLE IF NOT EXISTS acc_projects
-- Analytical projects
(
	id INTEGER NOT NULL PRIMARY KEY,

	code TEXT NULL,

	label TEXT NOT NULL,
	description TEXT NULL,

	archived INTEGER NOT NULL DEFAULT 0
);

CREATE UNIQUE INDEX IF NOT EXISTS acc_projects_code ON acc_projects (code);
CREATE INDEX IF NOT EXISTS acc_projects_list ON acc_projects (archived, code);

CREATE TABLE IF NOT EXISTS acc_years
-- Years (exercices)
(
	id INTEGER NOT NULL PRIMARY KEY,

	label TEXT NOT NULL,

	start_date TEXT NOT NULL CHECK (date(start_date) IS NOT NULL AND date(start_date) = start_date),
	end_date TEXT NOT NULL CHECK (date(end_date) IS NOT NULL AND date(end_date) = end_date),

	status INTEGER NOT NULL DEFAULT 0, -- 0 = open, 1 = closed, 2 = locked

	id_chart INTEGER NOT NULL REFERENCES acc_charts (id)
);

CREATE INDEX IF NOT EXISTS acc_years_status ON acc_years (status);

-- Make sure id_account is reset when a year is deleted
CREATE TRIGGER IF NOT EXISTS acc_years_delete BEFORE DELETE ON acc_years BEGIN
	UPDATE services_fees SET id_account = NULL, id_year = NULL WHERE id_year = OLD.id;
END;

CREATE TABLE IF NOT EXISTS acc_transactions
-- Transactions (écritures comptables)
(
	id INTEGER PRIMARY KEY NOT NULL,

	type INTEGER NOT NULL DEFAULT 0, -- Transaction type, zero is advanced
	status INTEGER NOT NULL DEFAULT 0, -- Status (bitmask)

	label TEXT NOT NULL,
	notes TEXT NULL,
	reference TEXT NULL, -- N° de pièce comptable

	date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(date) IS NOT NULL AND date(date) = date),

	hash TEXT NULL,
	prev_id INTEGER NULL REFERENCES acc_transactions(id) ON DELETE SET NULL,
	prev_hash TEXT NULL,

	id_year INTEGER NOT NULL REFERENCES acc_years(id),
	id_creator INTEGER NULL REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS acc_transactions_year ON acc_transactions (id_year);
CREATE INDEX IF NOT EXISTS acc_transactions_date ON acc_transactions (date);
CREATE INDEX IF NOT EXISTS acc_transactions_type ON acc_transactions (type, id_year);
CREATE INDEX IF NOT EXISTS acc_transactions_status ON acc_transactions (status);
CREATE INDEX IF NOT EXISTS acc_transactions_hash ON acc_transactions (hash);
CREATE INDEX IF NOT EXISTS acc_transactions_reference ON acc_transactions (reference);

CREATE TABLE IF NOT EXISTS acc_transactions_lines
-- Transactions lines (lignes des écritures)
(
	id INTEGER PRIMARY KEY NOT NULL,

	id_transaction INTEGER NOT NULL REFERENCES acc_transactions (id) ON DELETE CASCADE,
	id_account INTEGER NOT NULL REFERENCES acc_accounts (id),

	credit INTEGER NOT NULL,
	debit INTEGER NOT NULL,

	reference TEXT NULL, -- Usually a payment reference (par exemple numéro de chèque)
	label TEXT NULL,

	reconciled INTEGER NOT NULL DEFAULT 0,

	id_project INTEGER NULL REFERENCES acc_projects(id) ON DELETE SET NULL,

	CONSTRAINT line_check1 CHECK ((credit * debit) = 0),
	CONSTRAINT line_check2 CHECK ((credit + debit) > 0)
);

CREATE INDEX IF NOT EXISTS acc_transactions_lines_transaction ON acc_transactions_lines (id_transaction);
CREATE INDEX IF NOT EXISTS acc_transactions_lines_account ON acc_transactions_lines (id_account);
CREATE INDEX IF NOT EXISTS acc_transactions_lines_project ON acc_transactions_lines (id_project);
CREATE INDEX IF NOT EXISTS acc_transactions_lines_reconciled ON acc_transactions_lines (reconciled);

CREATE TABLE IF NOT EXISTS acc_transactions_links
(
	id_transaction INTEGER NOT NULL REFERENCES acc_transactions(id) ON DELETE CASCADE,
	id_related INTEGER NOT NULL REFERENCES acc_transactions(id) ON DELETE CASCADE CHECK (id_transaction != id_related),
	PRIMARY KEY (id_transaction, id_related)
);

CREATE INDEX IF NOT EXISTS acc_transactions_lines_id_transaction ON acc_transactions_links (id_transaction);
CREATE INDEX IF NOT EXISTS acc_transactions_lines_id_related ON acc_transactions_links (id_related);

CREATE TABLE IF NOT EXISTS acc_transactions_users
-- Linking transactions and users
(
	id_user INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
	id_transaction INTEGER NOT NULL REFERENCES acc_transactions (id) ON DELETE CASCADE,
	id_service_user INTEGER NULL REFERENCES services_users (id) ON DELETE SET NULL,

	PRIMARY KEY (id_user, id_transaction, id_service_user)
);

CREATE INDEX IF NOT EXISTS acc_transactions_users_service ON acc_transactions_users (id_service_user);

---------- FILES ----------------

CREATE TABLE IF NOT EXISTS files
-- Files metadata
(
	id INTEGER NOT NULL PRIMARY KEY,
	hash_id TEXT NOT NULL,
	path TEXT NOT NULL,
	parent TEXT NULL REFERENCES files(path) ON DELETE CASCADE ON UPDATE CASCADE,
	name TEXT NOT NULL, -- File name
	type INTEGER NOT NULL, -- File type, 1 = file, 2 = directory
	mime TEXT NULL,
	size INT NULL,
	modified TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(modified) IS NOT NULL AND datetime(modified) = modified),
	image INT NOT NULL DEFAULT 0,
	md5 TEXT NULL,
	trash TEXT NULL CHECK (datetime(trash) IS NULL OR datetime(trash) = trash),

	CHECK (type = 2 OR (mime IS NOT NULL AND size IS NOT NULL))
);

-- Unique index as this is used to make up a file path
CREATE UNIQUE INDEX IF NOT EXISTS files_unique ON files (path);
CREATE UNIQUE INDEX IF NOT EXISTS files_unique_hash ON files (hash_id);
CREATE INDEX IF NOT EXISTS files_parent ON files (parent);
CREATE INDEX IF NOT EXISTS files_type_parent ON files (type, parent, path);
CREATE INDEX IF NOT EXISTS files_name ON files (name);
CREATE INDEX IF NOT EXISTS files_modified ON files (modified);
CREATE INDEX IF NOT EXISTS files_trash ON files (trash);
CREATE INDEX IF NOT EXISTS files_size ON files (size);

CREATE TABLE IF NOT EXISTS files_contents
-- Files contents (empty if using another storage backend)
(
	id INTEGER NOT NULL PRIMARY KEY REFERENCES files(id) ON DELETE CASCADE,
	content BLOB NOT NULL
);

CREATE VIRTUAL TABLE IF NOT EXISTS files_search USING fts4
-- Search inside files content
(
	tokenize=unicode61, -- Available from SQLITE 3.7.13 (2012)
	path TEXT NOT NULL,
	title TEXT NOT NULL,
	content TEXT NULL, -- Text content
	notindexed=path
);

-- Delete/insert search item when item is deleted/inserted from files
CREATE TRIGGER IF NOT EXISTS files_search_bd BEFORE DELETE ON files BEGIN
	DELETE FROM files_search WHERE docid = OLD.rowid;
END;

CREATE TRIGGER IF NOT EXISTS files_search_ai AFTER INSERT ON files BEGIN
	INSERT INTO files_search (docid, path, title, content) VALUES (NEW.rowid, NEW.path, NEW.name, NULL);
END;

CREATE TRIGGER IF NOT EXISTS files_search_au AFTER UPDATE OF name, path ON files BEGIN
	UPDATE files_search SET path = NEW.path, title = NEW.name WHERE docid = NEW.rowid;
END;

CREATE TABLE IF NOT EXISTS files_shares
-- Sharing links for files
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_file INTEGER NOT NULL REFERENCES files(id) ON DELETE CASCADE,
	id_user INTEGER NULL REFERENCES users(id) ON DELETE CASCADE,
	created TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(created) IS NOT NULL AND datetime(created) = created),
	hash_id TEXT NOT NULL,
	option INTEGER NOT NULL,
	expiry TEXT NULL CHECK (datetime(expiry) IS NULL OR datetime(expiry) = expiry),
	password TEXT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS files_shares_hash ON files_shares (hash_id);
CREATE INDEX IF NOT EXISTS files_shares_file ON files_shares (id_file);
CREATE INDEX IF NOT EXISTS files_shares_expiry ON files_shares (expiry);

CREATE TABLE IF NOT EXISTS acc_transactions_files
-- Link between transactions and files
(
	id_file INTEGER NOT NULL PRIMARY KEY REFERENCES files(id) ON DELETE CASCADE,
	id_transaction INTEGER NOT NULL REFERENCES acc_transactions(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS acc_transactions_files_transaction ON acc_transactions_files (id_transaction);

CREATE TABLE IF NOT EXISTS users_files
-- Link between users and files
(
	id_file INTEGER NOT NULL PRIMARY KEY REFERENCES files(id) ON DELETE CASCADE,
	id_user INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
	field TEXT NOT NULL REFERENCES config_users_fields (name) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS users_files_user ON users_files (id_user);
CREATE INDEX IF NOT EXISTS users_files_user_field ON users_files (id_user, field);

CREATE TABLE IF NOT EXISTS web_pages
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_parent INTEGER NULL REFERENCES web_pages(id) ON DELETE CASCADE,
	uri TEXT NOT NULL, -- Page identifier
	type INTEGER NOT NULL, -- 1 = Category, 2 = Page
	status INTEGER NOT NULL,
	inherited_status INTEGER NOT NULL,
	format TEXT NOT NULL,
	published TEXT NOT NULL CHECK (datetime(published) IS NOT NULL AND datetime(published) = published),
	modified TEXT NOT NULL CHECK (datetime(modified) IS NOT NULL AND datetime(modified) = modified),
	title TEXT NOT NULL,
	content TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS web_pages_uri ON web_pages (uri);
CREATE INDEX IF NOT EXISTS web_pages_id_parent ON web_pages (id_parent);
CREATE INDEX IF NOT EXISTS web_pages_published ON web_pages (published);
CREATE INDEX IF NOT EXISTS web_pages_title ON web_pages (title);

CREATE TABLE IF NOT EXISTS web_pages_versions
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_page INTEGER NOT NULL REFERENCES web_pages ON DELETE CASCADE,
	id_user INTEGER NULL REFERENCES users (id) ON DELETE SET NULL,
	date TEXT NOT NULL CHECK (datetime(date) IS NOT NULL AND datetime(date) = date),
	size INTEGER NOT NULL,
	changes INTEGER NOT NULL,
	content TEXT NOT NULL
);
