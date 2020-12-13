<?php

namespace Garradin;

use Garradin\Entities\Web\Page;

use KD2\DB\EntityManager as EM;

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

    static public function listCategories(?int $parent): array
    {
        $where = $parent ? sprintf('parent_id = %d', $parent) : 'parent_id IS NULL';
        $sql = sprintf('SELECT * FROM @TABLE WHERE %s AND type = %d ORDER BY title COLLATE NOCASE;', $where, Page::TYPE_CATEGORY);
        return EM::getInstance(Page::class)->all($sql);
    }

    static public function listPages(?int $parent, bool $order_by_date = true): array
    {
        $where = $parent ? sprintf('parent_id = %d', $parent) : 'parent_id IS NULL';
        $order = $order_by_date ? 'modified DESC' : 'title COLLATE NOCASE';
        $sql = sprintf('SELECT * FROM @TABLE WHERE %s AND type = %d ORDER BY %s;', $where, Page::TYPE_PAGE, $order);
        return EM::getInstance(Page::class)->all($sql);
    }

    static public function get(int $id): Page
    {
        return EM::findOneById(Page::class, $id);
    }
}