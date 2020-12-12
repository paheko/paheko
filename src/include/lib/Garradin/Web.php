<?php

namespace Garradin;

class Web
{
    static public function search(string $search, bool $online_only = true): array
    {
        if (strlen($search) > 100) {
            throw new UserException('Recherche trop longue : maximum 100 caractères');
        }

        $where = '';

        if ($online_only) {
        	$where = sprintf('p.status = %d AND ', Page::STATUS_ONLINE);
        }

        $query = sprintf('SELECT
            p.*,
            snippet(files_search, \'<b>\', \'</b>\', \'...\', -1, -50) AS snippet,
            rank(matchinfo(files_search), 0, 1.0, 1.0) AS points
            FROM files_search AS s
            INNER JOIN web_pages AS p USING (id)
            WHERE %s files_search MATCH ?
            ORDER BY points DESC
            LIMIT 0,50;', $where);

        return DB::getInstance()->get($query, $search);
    }
}