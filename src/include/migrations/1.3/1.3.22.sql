-- Normalize currency
UPDATE config SET value = 'EUR' WHERE key = 'currency' AND (value LIKE '%€%' OR value LIKE '%EUR%');
UPDATE config SET value = 'XOF' WHERE key = 'currency' AND (value LIKE '%cfa%' OR value LIKE '%XAOF%');
UPDATE config SET value = 'MAG' WHERE key = 'currency' AND (value LIKE '%ariary%');
UPDATE config SET value = 'RND' WHERE key = 'currency' AND (value LIKE '%dt%');
UPDATE config SET value = 'MAD' WHERE key = 'currency' AND (value LIKE '%dh%');
UPDATE config SET value = 'ILD' WHERE key = 'currency' AND (value LIKE '%nis%');
UPDATE config SET value = 'GYD' WHERE key = 'currency' AND (value = 'G');

UPDATE config SET value = 'BRL' WHERE key = 'currency' AND value LIKE '%R$%';
UPDATE config SET value = 'USD' WHERE key = 'currency' AND value LIKE '%$%';

UPDATE config SET value = UPPER(value) WHERE key = 'currency';
