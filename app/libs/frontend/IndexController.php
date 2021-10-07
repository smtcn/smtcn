<?php

namespace app\libs\frontend;

use Api;

class IndexController extends BaseController
{
    public static function index($cid = 0, $page = 1)
    {
        //parent::randomString();

        Api::render('index', array(
            'title' => 'SMTCN',
        ));
    }
}
