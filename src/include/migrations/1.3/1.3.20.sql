CREATE TABLE IF NOT EXISTS web_suspicious_clients
(
	ip TEXT NOT NULL,
	expiry TEXT NOT NULL CHECK (datetime(expiry) = expiry),
	UNIQUE(ip)
);
