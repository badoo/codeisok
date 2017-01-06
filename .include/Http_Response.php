<?php

namespace GitPHP;

class Http_Response
{
    public $status_proto, $status_code, $status_text, $headers, $cookies, $body, $err;

    public function __construct($headers, $body)
    {
        list ($this->status_proto, $this->status_code, $this->status_text) = array_pad(explode(' ', array_shift($headers), 3), 3, null);
        $this->headers = self::parseHeaders($headers);
        if (!empty($this->headers['set-cookie'])) {
            $this->cookies = self::parseCookies($this->headers['set-cookie']);
        }
        list ($this->body, $this->err) = self::parseBody($body);
    }

    protected static function parseHeaders($headers)
    {
        $headers_parsed = [];
        foreach ($headers as $idx => $header) {
            $header_arr = explode(':', $header, 2);
            if (!isset($header_arr[1])) {
                throw new \RuntimeException("Bad header: $header");
            }
            $name = strtolower(trim($header_arr[0]));
            $value = trim($header_arr[1]);
            if (isset($headers_parsed[$name])) {
                if (!is_array($headers_parsed[$name])) $headers_parsed[$name] = [$headers_parsed[$name]];
                $headers_parsed[$name][] = $value;
            } else {
                $headers_parsed[$name] = $value;
            }
        }
        return $headers_parsed;
    }

    protected static function parseCookies($headers)
    {
        if (!is_array($headers)) $headers = [$headers];
        $result = [];
        foreach ($headers as $header) {
            $values = explode(';', $header);
            $cookie = [];
            $name = null;
            foreach ($values as $idx => $value) {
                $value_arr = explode('=', $value, 2);
                $value_value = isset($value_arr[1]) ? trim($value_arr[1]) : true;
                if ($idx == 0) {
                    $name = trim($value_arr[0]);
                    $cookie['value'] = $value_value;
                } else {
                    $cookie[strtolower($value_arr[0])] = $value_value;
                }
            }
            if (isset($result[$name])) {
                if (!is_array($result[$name])) $result[$name] = [$result[$name]];
                $result[$name][] = $cookie;
            } else {
                $result[$name] = $cookie;
            }
        }
        return $result;
    }

    protected static function parseBody($body)
    {
        $body_parsed = json_decode($body, true);
        if ($body_parsed === null && $body !== 'null') {
            $decode_err = json_last_error() . ':' . json_last_error_msg();
            return [$body, $decode_err];
        }
        return [$body_parsed, null];
    }
}
