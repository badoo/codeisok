<?php

namespace GitPHP;

class Redmine
{
    const API_KEY = '';
    const URL = 'https://your.redmine.url/';

    protected static $instance;

    public static function instance()
    {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function restAuthenticateByUsernameAndPassword($username, $password)
    {
        $data = json_encode(['username' => $username, 'password' => $password]);
        $Response = $this->request(
            self::URL,
            'GET',
            'users/current.json',
            [],
            ['Authorization: Basic ' . base64_encode($username . ':' . $password)]
        );

        $err = null;
        if ($Response->status_code != 200) {
            $err = isset($Response->body['message']) ? $Response->body['message'] : ($Response->status_code . $Response->status_text);
            return [null, $err];
        }

        $result = [
            'user_id' => $Response->body['user']['login'],
            'user_name' => $Response->body['user']['firstname'] . ' ' . $Response->body['user']['lastname'],
            'user_email' => empty($Response->body['user']['mail'])?'':$Response->body['user']['mail'],
            'user_token' => base64_encode($username . ':' . $password),
        ];
        return [$result, null];
    }

    protected function request($url, $http_method, $method, $data = null, $headers = [])
    {
        $Counter = new \CountClass(__METHOD__, "$http_method $method");
        $headers = array_merge($headers, [
            'Accept: application/json',
            'Content-Type: application/json',
        ]);

        if (!empty(self::API_KEY)) {
            array_push($headers, 'X-Redmine-API-Key: ' . self::APP_AUTH);
        }

        $opts = [
            'http' => [
                'method' => $http_method,
                'header' => implode("\r\n", $headers) . "\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ];

        if ($data !== null) $opts['http']['content'] = $data;

        $ctx = stream_context_create($opts);
        $url = $url . $method;
        $body = file_get_contents($url, null, $ctx);

        $Response = new Http_Response(isset($http_response_header) ? $http_response_header : [], $body);

        return $Response;
    }
}
