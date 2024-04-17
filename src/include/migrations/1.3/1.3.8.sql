ALTER TABLE acc_charts RENAME TO acc_charts_old;

-- Make country code nullable
CREATE TABLE IF NOT EXISTS acc_charts
-- Accounting charts (plans comptables)
(
	id INTEGER NOT NULL PRIMARY KEY,
	country TEXT NULL,
	code TEXT NULL, -- the code is NULL if the chart is user-created or imported
	label TEXT NOT NULL,
	archived INTEGER NOT NULL DEFAULT 0 -- 1 = archived, cannot be changed
);

INSERT INTO acc_charts SELECT * FROM acc_charts_old;
DROP TABLE acc_charts_old;

UPDATE files_search
	SET title = (SELECT title FROM web_pages WHERE path = 'web/' || uri)
	WHERE EXISTS (SELECT title FROM web_pages WHERE path = 'web/' || uri);

UPDATE config SET value = NULL WHERE value = '' AND key IN ('color1', 'color2');
