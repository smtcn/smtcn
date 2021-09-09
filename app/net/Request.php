<?php

namespace app\net;

use app\util\Collection;

class Request
{
    /**
     * @var string URL being requested
     */
    public $url;

    /**
     * @var string Parent subdirectory of the URL
     */
    public $base;

    /**
     * @var string Request method (GET, POST, PUT, DELETE)
     */
    public $method;

    /**
     * @var string Referrer URL
     */
    public $referrer;

    /**
     * @var string IP address of the client
     */
    public $ip;

    /**
     * @var bool Whether the request is an AJAX request
     */
    public $ajax;

    /**
     * @var string Server protocol (http, https)
     */
    public $scheme;

    /**
     * @var string Browser information
     */
    public $user_agent;

    /**
     * @var string Content type
     */
    public $type;

    /**
     * @var int Content length
     */
    public $length;

    /**
     * @var \app\util\Collection Query string parameters
     */
    public $query;

    /**
     * @var \app\util\Collection Post parameters
     */
    public $data;

    /**
     * @var \app\util\Collection Cookie parameters
     */
    public $cookies;

    /**
     * @var \app\util\Collection Uploaded files
     */
    public $files;

    /**
     * @var bool Whether the connection is secure
     */
    public $secure;

    /**
     * @var string HTTP accept parameters
     */
    public $accept;

    /**
     * @var string Proxy IP address of the client
     */
    public $proxy_ip;

    /**
     * @var string HTTP host name
     */
    public $host;

    /**
     * Constructor.
     *
     * @param array $config Request configuration
     */
    public function __construct($config = array())
    {
        // Default properties
        if (empty($config)) {
            $config = array(
                'url' => str_replace('@', '%40', self::getVar('REQUEST_URI', '/')),
                'base' => str_replace(array('\\', ' '), array('/', '%20'), dirname(self::getVar('SCRIPT_NAME'))),
                'method' => self::getMethod(),
                'referrer' => self::getVar('HTTP_REFERER'),
                'ip' => self::getVar('REMOTE_ADDR'),
                'ajax' => self::getVar('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest',
                'scheme' => self::getScheme(),
                'user_agent' => self::getVar('HTTP_USER_AGENT'),
                'type' => self::getVar('CONTENT_TYPE'),
                'length' => self::getVar('CONTENT_LENGTH', 0),
                'query' => new Collection($_GET),
                'data' => new Collection($_POST),
                'cookies' => new Collection($_COOKIE),
                'files' => new Collection($_FILES),
                'secure' => self::getScheme() == 'https',
                'accept' => self::getVar('HTTP_ACCEPT'),
                'proxy_ip' => self::getProxyIpAddress(),
                'host' => self::getVar('HTTP_HOST'),
            );
        }

        $this->init($config);
    }

    /**
     * Initialize request properties.
     *
     * @param array $properties Array of request properties
     */
    public function init($properties = array())
    {
        // Set all the defined properties
        foreach ($properties as $name => $value) {
            $this->$name = $value;
        }

        // Get the requested URL without the base directory
        if ($this->base != '/' && strlen($this->base) > 0 && strpos($this->url, $this->base) === 0) {
            $this->url = substr($this->url, strlen($this->base));
        }

        // Default url
        if (empty($this->url)) {
            $this->url = '/';
        }
        // Merge URL query parameters with $_GET
        else {
            $_GET += self::parseQuery($this->url);

            $this->query->setData($_GET);
        }

        // Check for JSON input
        if (strpos($this->type, 'application/json') === 0) {
            $body = $this->getBody();
            if ($body != '') {
                $data = json_decode($body, true);
                if (is_array($data)) {
                    $this->data->setData($data);
                }
            }
        }
    }

    /**
     * Gets the body of the request.
     *
     * @return string Raw HTTP request body
     */
    public static function getBody()
    {
        static $body;

        if (!is_null($body)) {
            return $body;
        }

        $method = self::getMethod();

        // $method == 'POST' || $method == 'PUT' || $method == 'DELETE' || $method == 'PATCH'
        if ($method == 'GET' || $method == 'POST' || $method == 'PUT' || $method == 'PATCH' || $method == 'DELETE' || $method == 'CONNECT' || $method == 'OPTIONS' || $method == 'HEAD' || $method == 'TRACE') {
            $handle = fopen('php://input', "r") or die("File does not exist!");
            $body = fread($handle, filesize('php://input'));
            fclose($handle);
        }

        return $body;
    }

    /**
     * Gets the request method.
     *
     * @return string
     */
    public static function getMethod()
    {
        $method = self::getVar('REQUEST_METHOD', 'GET');

        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
        } elseif (isset($_REQUEST['_method'])) {
            $method = $_REQUEST['_method'];
        }

        return strtoupper($method);
    }

    /**
     * Gets the real remote IP address.
     *
     * @return string IP address
     */
    public static function getProxyIpAddress()
    {
        static $forwarded = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED'
        );

        $flags = \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE;

        foreach ($forwarded as $key) {
            if (array_key_exists($key, $_SERVER)) {
                sscanf($_SERVER[$key], '%[^,]', $ip);
                if (filter_var($ip, \FILTER_VALIDATE_IP, $flags) !== false) {
                    return $ip;
                }
            }
        }

        return '';
    }

    /**
     * Gets a variable from $_SERVER using $default if not provided.
     *
     * @param string $var Variable name
     * @param string $default Default value to substitute
     * @return string Server variable value
     */
    public static function getVar($var, $default = '')
    {
        return isset($_SERVER[$var]) ? $_SERVER[$var] : $default;
    }

    /**
     * Parse query parameters from a URL.
     *
     * @param string $url URL string
     * @return array Query parameters
     */
    public static function parseQuery($url)
    {
        $params = array();

        $args = parse_url($url);
        if (isset($args['query'])) {
            parse_str($args['query'], $params);
        }

        return $params;
    }

    public static function getScheme()
    {
        if (
            (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on')
            ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            ||
            (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on')
            ||
            (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https')
        ) {
            return 'https';
        }
        return 'http';
    }
}
