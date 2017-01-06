<?php
/**
 * @author emakhrov
 * @date 14.03.13
 * @time 15:13
 */
/**
 * Define some paths
 */
define('GITPHP_BASEDIR', dirname(__FILE__) . '/');
define('GITPHP_CONFIGDIR', GITPHP_BASEDIR . '.config/');
define('GITPHP_INCLUDEDIR', GITPHP_BASEDIR . '.include/');
define('GITPHP_LIBDIR', GITPHP_BASEDIR . '.lib/');
define('GITPHP_GITOBJECTDIR', GITPHP_INCLUDEDIR . 'git/');
define('GITPHP_CONTROLLERDIR', GITPHP_INCLUDEDIR . 'controller/');
define('GITPHP_CACHEDIR', GITPHP_INCLUDEDIR . 'cache/');
define('GITPHP_LOCALEDIR', GITPHP_BASEDIR . '.locale/');
define('GITPHP_TEMPLATESDIR', GITPHP_BASEDIR . '.templates/');

spl_autoload_register(
    function($class) {
        static $map;
        if (!$map) {
            $map = require_once 'autoload.php';
        }

        if (isset($map[$class]) && file_exists($map[$class])) {
            require_once $map[$class];
        }
    }
);

class CountClass
{
    protected $name, $value;

    public function __construct($name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
        GitPHP_Log::GetInstance()->timerStart();
    }

    public function __destruct()
    {
        GitPHP_Log::GetInstance()->timerStop($this->name, $this->value);
    }
}

/* strlen() can be overloaded in mbstring extension, so always using mb_orig_strlen */
if (!function_exists('mb_orig_strlen')) {
    function mb_orig_strlen($str)
    {
        return strlen($str);
    }
}
if (!function_exists('mb_orig_substr')) {
    function mb_orig_substr($str, $offset, $len = null)
    {
        return isset($len) ? substr($str, $offset, $len) : substr($str, $offset);
    }
}
if (!function_exists('mb_orig_strpos')) {
    function mb_orig_strpos($haystack, $needle, $offset = 0)
    {
        return strpos($haystack, $needle, $offset);
    }
}
