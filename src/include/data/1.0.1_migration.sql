UPDATE acc_accounts SET position = 3 WHERE code = '445' OR code = '444' AND id_chart IN (SELECT id FROM acc_charts WHERE code = 'PCGA1999');
