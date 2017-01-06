<?php

namespace GitPHP;

class Error
{
    public static function errorHandler($errno, $errstr, $File, $Line)
    {
        if (!error_reporting()) return;
        static $errortype = array(
            E_ERROR           => '[Error]',
            E_WARNING         => '[Warning]',
            E_PARSE           => '[Parsing Error]',
            E_NOTICE          => '[Notice]',
            E_CORE_ERROR      => '[Core Error]',
            E_CORE_WARNING    => '[Core Warning]',
            E_COMPILE_ERROR   => '[Compile Error]',
            E_COMPILE_WARNING => '[Compile Warning]',
            E_USER_ERROR      => '[Error]',
            E_USER_WARNING    => '[Warning]',
            E_STRICT          => '[Strict]',
            E_USER_NOTICE     => '[Notice]',
            E_RECOVERABLE_ERROR => '[Fatal Error]',
        );

        $message = (isset($errortype[$errno]) ? $errortype[$errno] . ' ' : '') . $errstr;
        $message .= "\n" . (new \Exception())->getTraceAsString();

        if (php_sapi_name() != 'cli') {
            $message .= self::getAdditionalInfoForError();
        }

        error_log("($errno) $message");
    }

    public static function getAdditionalInfoForError()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $message = "##uncutable##";
        $message .= ' | URL [' . $method . '] : ';
        $message .= '//' . $_SERVER['HTTP_HOST'];

        if (isset($_SERVER["REQUEST_URI"])) {
            $message .= $_SERVER["REQUEST_URI"];
        }
        if (isset($_SERVER['DOCUMENT_URI'])) {
            $message .= ' (DOCUMENT_URI: ' . $_SERVER['DOCUMENT_URI'] . ') ';
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $message .= ' (REMOTE_ADDR: ' . $_SERVER['REMOTE_ADDR'] . ') ';
        }

        $message .= " | PID=" . posix_getpid();

        if (/*security concerns*/false && $method === 'POST') {
            $message .= ' | POST: ' . var_export($_POST, true);
        }

        if (/*security concerns*/false) {
            $message .= ' | GET: ' . var_export($_GET, true);
        }

        $message .= ' | CLIENT DATA: ' . Error::getClientParamsString();
        return $message;
    }

    public static function getClientParams()
    {
        $params = [];
        $keys = [
            'HTTP_REFERER',
            'HTTP_USER_AGENT',
            /*security concerns*//*'HTTP_COOKIE',*/
            'HTTP_X_FORWARDED_FOR',
            'HTTP_VIA',
            'HTTP_X_CLIENT_IP',
        ];
        foreach ($keys as $i_key) {
            if (isset($_SERVER[$i_key])) {
                $params[$i_key] = $_SERVER[$i_key];
            } else {
                $params[$i_key] = '[empty]';
            }
        }
        if (isset($params['HTTP_COOKIE'])) {
            $params['HTTP_COOKIE'] = preg_replace('/email=.*?;/ius', '', $params['HTTP_COOKIE']);
        }

        return $params;
    }

    public static function getClientParamsString()
    {
        $params = Error::getClientParams();
        $result = '';
        foreach ($params as $i_key => $i_value) {
            if ($result) $result .= ', ';
            $result .= $i_key . ' = ' . $i_value;
        }

        return $result;
    }
}
