<?php

class Api
{
    /**
     * Framework engine.
     *
     * @var \app\Engine
     */
    private static $engine;

    // Don't allow object instantiation
    private function __construct(){}
    private function __destruct(){}
    private function __clone(){}

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed Callback results
     * @throws \Exception
     */
    public static function __callStatic($name, $params)
    {
        $app = Api::app();

        return \app\core\Dispatcher::invokeMethod(array($app, $name), $params);
    }

    /**
     * @return \app\Engine Application instance
     */
    public static function app()
    {
        static $initialized = false;

        if (!$initialized) {
            require_once __DIR__ . '/core/Loader.php';

            \app\core\Loader::autoload(true, dirname(__DIR__));

            self::$engine = new \app\Engine();

            $initialized = true;
        }

        return self::$engine;
    }
}
