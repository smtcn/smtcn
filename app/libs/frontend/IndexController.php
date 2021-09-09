<?php

namespace app\libs\frontend;

use \Api;

class IndexController
{

    /**
     * 首页
     * @param  [type] $cid  [description]
     * @param  [type] $page [description]
     * @return [type]       [description]
     */
    public static function index($cid, $page)
    {
        $data = 'baidu';

        Api::render('index', [
            'data' => $data,
        ]);
    }
}
