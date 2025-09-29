<?php

namespace App\Classes;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class SimpleNavigatorHandler
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    static function pageNavigation(
        Collection|SupportCollection|NULL $collection = null,
        array|NULL $array = null,
        int|null $page = 1,
        int|null $pageSize = 36,
    ): array {
        $page = $page ?: 1;
        $pageSize = $pageSize ?: 36;
        
        if ($pageSize == 0) $pageSize = 25;
        if ($page == 0) $page = 1;

        if ($collection == null && $array == null) return [
            'page_size'         => $pageSize,
            'has_next'          => 0 > $page + 1,
            'has_previous'      => $page > 0,
            'pages'             => [],
            'current_page'      => $page,
            'total_contents'    => 0,
            'contents'          => [],
        ];

        $page -= 1;
        if ($page < 0) $page = 0;

        $datas = $collection ?: collect($array);
        $pages = [0];
        for ($i = 0; $i < intval(($datas->count() == 0 ? 1 : $datas->count()) / $pageSize); $i++) {
            $pages[] = $pageSize * $i;
        }

        $contents = $datas->slice($page * $pageSize, $pageSize);

        return [
            'page_size'         => $pageSize,
            'has_next'          => count($pages) > $page + 1,
            'has_previous'      => $page > 0,
            'pages'             => $pages,
            'current_page'      => $page + 1,
            'total_contents'    => $datas->count(),
            'contents'          => array_values($contents->toArray()),
        ];
    }
}
