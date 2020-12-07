<?php
require_once __DIR__ . '/vendor/autoload.php';

const GITPHP_BASEDIR = __DIR__ . '/';
const GITPHP_CONFIGDIR = GITPHP_BASEDIR . '.config/';
const GITPHP_LOCALEDIR = GITPHP_BASEDIR . 'resources/locale/';
const GITPHP_TEMPLATESDIR = GITPHP_BASEDIR . 'resources/templates/';
const GITPHP_TEMPLATESCACHEDIR = GITPHP_BASEDIR . 'templates_c/';
const GITPHP_CSSDIR = GITPHP_BASEDIR . 'public/css/';
const GITPHP_JSDIR = GITPHP_BASEDIR . 'public/js/';
const GITPHP_LIBDIR = GITPHP_BASEDIR . 'public/lib/';

class CountClass
{
    protected $name, $value;

    public function __construct($name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
        \GitPHP\Log::GetInstance()->timerStart();
    }

    public function __destruct()
    {
        \GitPHP\Log::GetInstance()->timerStop($this->name, $this->value);
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

/**
 * Gettext wrapper function for readability, single string
 *
 * @param string $str string to translate
 * @return string translated string
 */
function __($str)
{
    if (\GitPHP\Resource::Instantiated())
        return \GitPHP\Resource::GetInstance()->translate($str);
    return $str;
}

/**
 * Gettext wrapper function for readability, plural form
 *
 * @param string $singular singular form of string
 * @param string $plural plural form of string
 * @param int $count number of items
 * @return string translated string
 */
function __n($singular, $plural, $count)
{
    if (\GitPHP\Resource::Instantiated())
        return \GitPHP\Resource::GetInstance()->ngettext($singular, $plural, $count);
    if ($count > 1)
        return $plural;
    return $singular;
}