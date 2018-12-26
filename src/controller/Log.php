<?php

namespace Jeristiano\Atals\controller;
use Jeristiano\AtlasLog\model\Log as LogModel;
use think\paginator\driver\Bootstrap as BootstrapPaginator;

class Log
{

    public function show()
    {
        $page = request()->param('page', 1);
        $menu_page = request()->param('menu_page', 1);
        $pageSize = request()->param('page_size', 20);
        $file = request()->param('file');
        $log = new LogModel();
        $files = array_reverse($log->files());
        $menu = $this->menu($menu_page, $files, 20);
        $files = $this->page($files, $menu_page, 20);
        $default = $file ?: ($files[0]['real']);
        $data = $default ? $log->paginate($default, $page, $pageSize) : [];
        // 分页信息
        $paginator = BootstrapPaginator::make($data['data'], $data['meta']['page_size'], $data['meta']['current_page'], $data['meta']['total'], false, [
            'path' => '',
            'query' => request()->param(),
        ])->render();
        return view('log/index', compact('files', 'default', 'data', 'paginator', 'menu'));
    }

    private function menu($menu_page, $files, $limit = 20)
    {
        $totals = count($files);
        $countpage = ceil($totals / $limit); #计算总页面数
        $menu['current_page'] = $menu_page;
        $menu['total'] = $countpage;
        if ($menu_page <= 1) {
            $previous = 1;
        } else {
            $previous = $menu_page - 1;
        }
        if ($menu_page >= $countpage) {
            $next = $countpage;
        } else {
            $next = $menu_page + 1;
        }
        $menu['previous'] = $previous;
        $menu['next'] = $next;
        $menu['first'] = 1;
        return $menu;
    }

    /**
     * 分页
     * @param $list
     * @param $page
     * @param $limit
     * @return array|bool
     */
    private function page($list, $page, $limit)
    {
        if (!$list) return false;
        $page = (empty($page)) ? '1' : $page;
        $start = ($page - 1) * $limit; #计算每次分页的开始位置
        $totals = count($list);
        $countpage = ceil($totals / $limit); #计算总页面数
        if ($page <= $countpage) {
            $pagelist = array_slice($list, $start, $limit);
        } else {
            $pagelist = [];
        }
        return $pagelist;
    }

}
