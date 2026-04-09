CREATE TABLE IF NOT EXISTS web_suspicious_clients
(
	ip TEXT NOT NULL,
	expiry TEXT NOT NULL CHECK (datetime(expiry) = expiry),
	UNIQUE(ip)
);

CREATE TABLE IF NOT EXISTS web_pages_uris
(
	id_page INTEGER NOT NULL REFERENCES web_pages ON DELETE CASCADE,
	uri TEXT NOT NULL,
	UNIQUE (uri)
);
