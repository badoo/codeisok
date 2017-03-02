<?php

namespace GitPHP;

class Jira
{
    const CROWD_COOKIE_NAME = 'crowd.token_key';
    const REST_COOKIE_NAME = 'studio.crowd.tokenkey';
    const APP_AUTH = '';
    const CROWD_URL = 'http://crowd.yourjiraurl.com:8095/crowd/rest/';
    const URL = 'https://yourjira.atlassian.net/';

    protected static $instance;

    public static function instance()
    {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public static function getCookieName()
    {
        $cookie_name = self::CROWD_COOKIE_NAME;
        if (\GitPHP_Config::AUTH_METHOD['jira']) {
            $cookie_name = self::REST_COOKIE_NAME;
        }
        return $cookie_name;
    }

    public function restAuthenticateByUsernameAndPassword($username, $password)
    {
        $data = json_encode(['username' => $username, 'password' => $password]);
        $Response = $this->request(self::URL, 'POST', 'rest/auth/1/session', $data, ['X-Atlassian-Token: nocheck']);

        $err = null;
        if ($Response->status_code != 200) {
            $err = isset($Response->body['message']) ? $Response->body['message'] : ($Response->status_code . $Response->status_text);
            return [null, $err];
        }

        if (isset($Response->cookies[self::getCookieName()])) {
            $session = $Response->cookies[self::getCookieName()][0]['value'];
            return $this->restAuthenticateByCookie($session);
        } else {
            $err = 'Can\'t get session cookies from REST response!';
        }
        return [null, $err];
    }

    public function restAuthenticateByCookie($cookie) {
        $err = null;
        $Myself = $this->request(self::URL, 'GET', 'rest/api/2/myself', null, ['X-Atlassian-Token: nocheck', 'Cookie: ' . self::getCookieName() . '=' . $cookie]);
        if ($Myself->status_code != 200) {
            $err = 'REST authentication failure!';
        }
        if ($err) return [null, $err];

        $result = [
            'user_id' => $Myself->body['key'],
            'user_name' => $Myself->body['displayName'],
            'user_email' => $Myself->body['emailAddress'],
            'user_token' => $cookie,
        ];

        return [$result, null];
    }

    public function crowdAuthenticatePrincipalByCookie($crowd_token_key)
    {
        $Response = $this->request(self::CROWD_URL, 'GET', 'usermanagement/latest/session/' . urlencode($crowd_token_key));

        $err = null;
        if ($Response->status_code != 200) {
            $err = isset($Response->body['message']) ? $Response->body['message'] : ($Response->status_code . $Response->status_text);
        }
        if (!isset($Response->body['user']['name']) || !isset($Response->body['user']['display-name']) || !isset($Response->body['user']['email'])) {
            $err = 'Bad response:' . print_r($Response->body, true);
        }

        if ($err) return [null, $err];

        $result = [
            'user_id' => $Response->body['user']['name'],
            'user_name' => $Response->body['user']['display-name'],
            'user_email' => $Response->body['user']['email'],
            'user_token' => $crowd_token_key,
        ];

        return [$result, null];
    }

    public function crowdAuthenticatePrincipal($login, $password)
    {
        $data = json_encode(['value' => $password]);
        $Response = $this->request(self::CROWD_URL, 'POST', 'usermanagement/latest/authentication?username=' . urlencode($login), $data);

        $err = null;
        if ($Response->status_code != 200) {
            $err = isset($Response->body['message']) ? $Response->body['message'] : ($Response->status_code . $Response->status_text);
        } else if ($Response->err) {
            $err = $Response->err;
        } else if (!isset($Response->body['name']) || !isset($Response->body['display-name']) || !isset($Response->body['email'])) {
            $err = 'Bad response:' . print_r($Response->body, true);
        }

        if ($err) return [null, $err];

        $result = [
            'user_id' => $Response->body['name'],
            'user_name' => $Response->body['display-name'],
            'user_email' => $Response->body['email'],
        ];

        $data = json_encode(['username' => $login, 'password' => $password]);
        $Response = $this->request(self::CROWD_URL, 'POST', 'usermanagement/latest/session', $data);

        if ($Response->status_code != 201) {
            $err = isset($Response->body['message']) ? $Response->body['message'] : ($Response->status_code . $Response->status_text);
        }
        if (!isset($Response->body['token'])) {
            $err = 'Bad response:' . print_r($Response->body, true);
        }

        if ($err) return [null, $err];

        $result['user_token'] = $Response->body['token'];

        return [$result, null];
    }

    public function crowdIsGroupMember($user_id, $group_name)
    {
        $query = [
            'username' => $user_id,
            'groupname' => $group_name,
        ];
        $query_str = http_build_query($query);
        $Response = $this->request(self::CROWD_URL, 'GET', 'usermanagement/latest/user/group/direct?' . $query_str);
        if ($Response->status_code != 200 || empty($Response->body['name'])) {
            return false;
        }
        return true;
    }

    public function restIsGroupMember($user_id, $group_name)
    {
        if (!empty($_SERVER['HTTP_COOKIE'])) {
            $auth_cookie = '';
            $cookies = explode(" ", $_SERVER['HTTP_COOKIE']);
            foreach ($cookies as $c) {
                if (strpos($c, self::getCookieName()) !== false) {
                    $auth_cookie = $c;
                    break;
                }
            }
            $User = $this->request(
                self::JIRA_URL,
                'GET',
                'rest/api/2/user?username=' . urlencode($user_id) . '&expand=groups',
                null,
                ['X-Atlassian-Token: nocheck', 'Cookie: ' . $auth_cookie]
            );
            if ($User->status_code == 200 && !empty($User->body['groups']) && !empty($User->body['groups']['items'])) {
                foreach ($User->body['groups']['items'] as $Group) {
                    if ($Group['name'] == $group_name) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    protected function request($url, $http_method, $method, $data = null, $headers = [])
    {
        $Counter = new \CountClass(__METHOD__, "$http_method $method");
        $headers = array_merge($headers, [
            'Accept: application/json',
            'Content-Type: application/json',
        ]);

        if (!empty(self::APP_AUTH)) {
            array_push($headers, 'Authorization: Basic ' . self::APP_AUTH);
        }

        $opts = [
            'http' => [
                'method' => $http_method,
                'header' => implode("\r\n", $headers) . "\r\n",
                'ignore_errors' => true,
            ],
        ];
        if ($data !== null) $opts['http']['content'] = $data;

        $ctx = stream_context_create($opts);

        $url = $url . $method;

        $body = file_get_contents($url, null, $ctx);

        $Response = new Http_Response(isset($http_response_header) ? $http_response_header : [], $body);

        return $Response;
    }
}
