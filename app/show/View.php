<?php

namespace app\show;

class View
{
    /**
     * Location of view templates.
     *
     * @var string
     */
    public $path;

    /**
     * File extension.
     *
     * @var string
     */
    public $extension = '.php';

    /**
     * Cache directory.
     *
     * @var string
     */
    public $cache = './theme/cache';

    /**
     * Cache times.
     *
     * @var integer
     */
    public $cache_time = 0;

    /**
     * View variables.
     *
     * @var array
     */
    protected $vars = array();

    /**
     * Template file.
     *
     * @var string
     */
    private $template;

    /**
     * Template replace.
     *
     * @var array
     */
    private $system_replace = array(
        '~\{(\$[a-z0-9_]+)\}~i' => '<?php echo $1 ?>',
        # {$name}

        '~\{(\$[a-z0-9_]+)\.([a-z0-9_]+)\}~i' => '<?php echo $1[\'$2\'] ?>',
        # {$arr.key}

        '~\{(\$[a-z0-9_]+)\.([a-z0-9_]+)\.([a-z0-9_]+)\}~i' => '<?php echo $1[\'$2\'][\'$3\'] ?>',
        # {$arr.key.key2}

        '~\{(include_once|require_once|include|require)\s*\(\s*(.+?)\s*\)\s*\s*\}~i' => '<?php include_once \$this->_include($2, __FILE__); ?>',
        # {include('inc/top.php')}

        '~\{:(.+?)\}~' => '<?php echo $1 ?>',
        # {:strip_tags($a)}

        '~\{loop\s+(\S+)\s+(\S+)\}~' => '<?php if(is_array(\\1)) foreach(\\1 as \\2) { ?>',
        # {loop $array $vaule}

        '~\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}~' => '<?php if(is_array(\\1)) foreach (\\1 as \\2 => \\3) { ?>',
        # {loop $array $key $value}

        '~\{\/loop\}~' => '<?php } ?>',
        # {/loop}

        '~\{if\s+(.+?)\}~' => '<?php if (\\1) { ?>',
        # {if condition}

        '~\{elseif\s+(.+?)\}~' => '<?php }elseif(\\1){ ?>',
        # {elseif condition}

        '~\{else\}~' => '<?php }else{ ?>',
        # {else}

        '~\{\/if\}~' => '<?php } ?>',
        # {/if}

        '~\<\?php\s+die\(\'Access Denied\'\);\s+\?\>~' => '',
        # Access Denied
    );

    /**
     * Constructor.
     *
     * @param string $path Path to templates directory
     */
    public function __construct($path = '.')
    {
        $this->path = $path;
    }

    /**
     * Citation file.
     *
     * @param string $file Template file
     * @throws \Exception If template not found
     */
    public function _include($file)
    {
        $this->template = $this->getTemplate($file);

        if (!file_exists($this->template)) {
            throw new \Exception("Template file not found: {$this->template}.");
        }

        if (!is_dir($this->cache)) {
            $this->mkdirs($this->cache);
        }

        $tmpPath = $this->cache . '/' . md5(str_replace('/', '_', $this->template)) . $this->extension;

        if (!$this->isCached($tmpPath)) {
            $handle = fopen($this->template, "r") or die("File does not exist!");
            $tmpData = fread($handle, filesize($this->template));
            fclose($handle);

            $tpl = preg_replace(array_keys($this->system_replace), $this->system_replace, $tmpData);

            $handle = fopen($tmpPath, "w") or die("File does not exist!");
            fwrite($handle, trim($tpl) . PHP_EOL);
            fclose($handle);
        }

        return $tmpPath;
    }

    /**
     * Gets a template variable.
     *
     * @param string $key Key
     * @return mixed Value
     */
    public function get($key)
    {
        return isset($this->vars[$key]) ? $this->vars[$key] : null;
    }

    /**
     * Sets a template variable.
     *
     * @param mixed $key Key
     * @param string $value Value
     */
    public function set($key, $value = null)
    {
        if (is_array($key) || is_object($key)) {
            foreach ($key as $k => $v) {
                $this->vars[$k] = $v;
            }
        } else {
            $this->vars[$key] = $value;
        }
    }

    /**
     * Checks if a template variable is set.
     *
     * @param string $key Key
     * @return boolean If key exists
     */
    public function has($key)
    {
        return isset($this->vars[$key]);
    }

    /**
     * Unsets a template variable. If no key is passed in, clear all variables.
     *
     * @param string $key Key
     */
    public function clear($key = null)
    {
        if (is_null($key)) {
            $this->vars = array();
        } else {
            unset($this->vars[$key]);
        }
    }

    /**
     * Renders a template.
     *
     * @param string $file Template file
     * @param array $data Template data
     * @throws \Exception If template not found
     */
    public function render($file, $data = null)
    {
        $this->template = $this->getTemplate($file);

        if (!file_exists($this->template)) {
            throw new \Exception("Template file not found: {$this->template}.");
        }

        if (is_array($data)) {
            $this->vars = array_merge($this->vars, $data);
        }

        extract($this->vars);

        if (!is_dir($this->cache)) {
            $this->mkdirs($this->cache);
        }

        $tmpPath = $this->cache . '/' . md5(str_replace('/', '_', $this->template)) . $this->extension;

        if (!$this->isCached($tmpPath)) {
            $handle = fopen($this->template, "r") or die("File does not exist!");
            $tmpData = fread($handle, filesize($this->template));
            fclose($handle);

            $tpl = preg_replace(array_keys($this->system_replace), $this->system_replace, $tmpData);

            $handle = fopen($tmpPath, "w") or die("File does not exist!");
            fwrite($handle, trim($tpl) . PHP_EOL);
            fclose($handle);
        }

        include $tmpPath;
    }

    /**
     * Gets the output of a template.
     *
     * @param string $file Template file
     * @param array $data Template data
     * @return string Output of template
     */
    public function fetch($file, $data = null)
    {
        ob_start();

        $this->render($file, $data);
        $output = ob_get_clean();

        return $output;
    }

    /**
     * Checks if a template file exists.
     *
     * @param string $file Template file
     * @return bool Template file exists
     */
    public function exists($file)
    {
        return file_exists($this->getTemplate($file));
    }

    /**
     * Gets the full path to a template file.
     *
     * @param string $file Template file
     * @return string Template file location
     */
    public function getTemplate($file)
    {
        $ext = $this->extension;

        if (!empty($ext) && (substr($file, -1 * strlen($ext)) != $ext)) {
            $file .= $ext;
        }

        if ((substr($file, 0, 1) == '/')) {
            return $file;
        }

        return $this->path . '/' . $file;
    }

    /**
     * Gets the full path to a is cached.
     *
     * @param string $file Cached file
     * @return boolean Cached file boolean
     */
    private function isCached($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        $cacheTime = $this->cache_time;

        if ($cacheTime < 0) {
            return true;
        }

        if (time() - filemtime($file) > $cacheTime) {
            return false;
        }

        return true;
    }

    /**
     * Create a folder recursively
     *
     * @param string $file path
     * @return boolean path boolean
     */
    private function mkdirs($file)
    {
        if (!is_dir(dirname($file))) {
            $this->mkdirs(dirname($file));
        }
        return mkdir($file, 0750);
    }

    /**
     * Displays escaped output.
     *
     * @param string $str String to escape
     * @return string Escaped string
     */
    public function e($str)
    {
        echo htmlentities($str);
    }
}
