<?php

namespace GitPHP;

class RedmineRestClient
{
    const
        REQ_GET       = 'GET',
        REQ_POST      = 'POST',
        REQ_PUT       = 'PUT',
        REQ_DELETE    = 'DELETE',
        REQ_MULTIPART = 'MULTIPART';

    const API_KEY = 'yourapikey';

    const REST_URL = '/';

    protected static $instance = null;
    protected $url;

    /**
     * @return RedmineRestClient
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
            self::$instance->setUrl(\GitPHP\Redmine::URL);
        }
        return self::$instance;
    }

    public function searchUserByName($user)
    {
        return $this->_get('users.json', ['name' => $user]);
    }

    public function getUser($user_id)
    {
        $user_id = (int)$user_id;
        return $this->_get('users/' . $user_id . '.json');
    }

    public function getIssue($issue_key)
    {
        $issue_key = (int)$issue_key;
        return $this->_get('issues/' . $issue_key . '.json');
    }

    public function addComment($issue_key, $comment)
    {
        $issue_key = (int)$issue_key;
        $issue = new \StdClass();
        $issue->notes = $comment;
        return $this->_put('issues/' . $issue_key . '.json', ['issue' => $issue]);
    }

    private function _get($command, $arguments = [])
    {
        return $this->_request(self::REQ_GET, $command, $arguments);
    }

    private function _post($command, $arguments = [])
    {
        return $this->_request(self::REQ_POST, $command, $arguments);
    }

    private function _multipart($command, $arguments)
    {
        return $this->_request(self::REQ_MULTIPART, $command, $arguments);
    }

    private function _put($command, $arguments = [])
    {
        return $this->_request(self::REQ_PUT, $command, $arguments);
    }

    private function _delete($command)
    {
        return $this->_request(self::REQ_DELETE, $command);
    }

    /**
     * Make a request to REST API and parse response.
     * Return Array with response data or null for empty response body.
     *
     * @param string $method   - HTTP request method (e.g. HEAD/PUT/GET...)
     * @param string $command  - API method path (e.g. issue/<key>)
     * @param array $arguments - request data (parameters)
     *
     * @return array|null      - array (parsed response JSON) or null (on 204 response code with empty body) for
     *                           successful request.
     *
     * @throws Exception - on JSON parse errors, on warning HTTP codes and other errors.
     */
    private function _request($method, $command, $arguments = [])
    {
        $url = $this->getUrl() . self::REST_URL . $command;
        if ($method == self::REQ_GET && !empty($arguments)) {
            $url = $url . '?' . http_build_query($arguments);
        }

        $curl_options = [
            CURLOPT_URL            => $url,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $header_options = [
            'Accept'            => 'application/json',
            'Content-Type'      => 'application/json',
            'X-Redmine-API-Key' => self::API_KEY,
        ];


        switch ($method) {
            case self::REQ_POST:
                $arguments = json_encode($arguments);
                $curl_options[CURLOPT_POST]       = true;
                $curl_options[CURLOPT_POSTFIELDS] = $arguments;
                break;

            case self::REQ_MULTIPART:
                $header_options['Content-Type']   = 'multipart/form-data';
                $curl_options[CURLOPT_POST]       = true;
                $curl_options[CURLOPT_POSTFIELDS] = $arguments;
                break;

            case self::REQ_PUT:
                $arguments = json_encode($arguments);
                $curl_options[CURLOPT_CUSTOMREQUEST] = self::REQ_PUT;
                $curl_options[CURLOPT_POST]          = true;
                $curl_options[CURLOPT_POSTFIELDS]    = $arguments;
                break;

            case self::REQ_DELETE:
                $curl_options[CURLOPT_CUSTOMREQUEST] = self::REQ_DELETE;
                break;

            default:
        }

        $headers = [];
        foreach ($header_options as $opt_name => $opt_value) {
            $headers[] = "$opt_name: $opt_value";
        }
        $curl_options[CURLOPT_HTTPHEADER] = $headers;

        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $http_code = $info['http_code'];
        if ($http_code > 200 and $http_code < 300 and empty($result)) {
            return null;  // sometimes api returns response with empty body (e.g. for 201, 204 codes).
        }
        if (!empty($result)) {
            $result = json_decode($result);
            $error = json_last_error();

            if (JSON_ERROR_NONE !== $error) {
                throw new \Exception(json_last_error_msg());
            }
        }
        if (isset($result->errorMessages) && !empty($result->errorMessages)) {
            throw new \Exception("Errors occurred when performed api call: " . implode('; ', $result->errorMessages));
        }
        if ($http_code > 300) {
            throw new \Exception("Unknown error occurred when tried to perform api call. API answer: " . var_export($result, 1));
        }
        return $result;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }
}
