<?php

namespace app\libs\frontend;

class BaseController
{
    /**
     * 生成随机字符串
     * @method randomString
     * @param  integer        $length [description]
     * @return [type]                 [description]
     */
    protected static function randomString($length = 16)
    {
        $chars = array_merge(range("a", "z"), range("A", "Z"), range(0, 9), str_split('!@#$%^&*()-_ []{}<>~`+=,.;:/?|'));
        shuffle($chars);
        return implode(array_slice($chars, 1, $length));
    }
}
