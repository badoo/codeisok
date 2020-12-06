<?php

namespace GitPHP;

class JiraRestClient
{
    const
        REQ_GET       = 'GET',
        REQ_POST      = 'POST',
        REQ_PUT       = 'PUT',
        REQ_DELETE    = 'DELETE',
        REQ_MULTIPART = 'MULTIPART';

    const REST_URL = 'rest/api/latest/';

    /**
     * Expansion groups. Additional information for issue to be requested from Jira API.
     * Constants are used as $expand parameters in get issue information methods (::getIssue(),
     * getIssuesFromJqlSearch(), ...)
     *
     * Look at ::getIssue() DocBlock for more information.
     */
    const
        EXP_CHANGELOG       = 'changelog',
        EXP_RENDERED_FIELDS = 'renderedFields';

    protected static $instance = null;

    protected $all_fields = null;
    protected $statuses = null;
    protected $users_cache = [];

    protected $jira_url      = '';
    protected $jira_user     = '';
    protected $jira_password = '';

    /**
     * @return JiraRestClient
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
            self::$instance->jira_url = \GitPHP\Config::GetInstance()->GetJiraUrl();
            self::$instance->jira_user = \GitPHP\Config::GetInstance()->GetJiraUser();
            self::$instance->jira_password = \GitPHP\Config::GetInstance()->GetJiraPassword();
        }
        return self::$instance;
    }

    public function getProject($project_key)
    {
        return $this->_get("project/{$project_key}");
    }

    public function getProjectComponents($project_key)
    {
        return $this->_get("project/{$project_key}/components");
    }

    public function getCreateMetaInfo($project_key = '')
    {
        if ($project_key) {
            $project_key = '?projectKeys=' . $project_key;
        }
        return $this->_get('issue/createmeta' . $project_key);
    }

    /**
     * @param string $project    - project key (use Jira::PROJECT_* constants)
     * @param string $issue_type - issue type name (use (Jira::ISSUE_TYPE_* constants)
     * @param string $summary    - new issue summary
     * @param array $fields      - new issue field values.
     * @return \stdClass
     *
     * @throws \Exception
     */
    public function createIssue($project, $issue_type, $summary, $fields = [])
    {
        $MetaInfo = $this->getCreateMetaInfo($project);
        $for_project = array_filter(
            $MetaInfo->projects,
            function ($MetaInfo) use ($project) { return $MetaInfo->key == $project; }
        );
        $MetaInfo = array_shift($for_project);
        $available_issue_types = $MetaInfo->issuetypes;
        foreach ($available_issue_types as $Type) {
            if ($Type->name == $issue_type) {
                $issue_type = ['id' => $Type->id];
            }
        }

        $fields['project'] = ['key' => $project];
        $fields['issuetype'] = $issue_type;
        $fields['summary'] = $summary;
        return $this->_post('issue', ['fields' => $fields]);
    }

    /**
     * @param string $key      - issue key to get information for.
     * @param string[] $expand - provide extra information for issue. Use ::EXP_* constants as values here.
     *                           ( Look for 'Expansion' section at https://docs.atlassian.com/jira/REST/cloud/ )
     * @return \stdClass
     */
    public function getIssue($key, $expand = [])
    {
        $arguments = [];

        $expand = implode(',', $expand);
        if (!empty($expand)) $arguments['expand'] = $expand;

        return $this->_get("issue/{$key}", $arguments);
    }

    /**
     * @param {string} $jql    - search request line.
     * @param int $start_at    - results list shift (skip first N results)
     * @param int $max_results - maximum number of results to be returned.
     * @param string[] $fields - list of fields to get in response. By default all known issue's fields are returned.
     * @param string[] $expand - provide extra information for issue. Use ::EXP_* constants as values here.
     *                           ( Look for 'Expansion' section at https://docs.atlassian.com/jira/REST/cloud/ )
     * @return array
     */
    public function getIssuesFromJqlSearch($jql, $start_at = 0, $max_results = 1000, $fields = [], $expand = [])
    {
        $result = $this->_post(
            'search/',
            array(
                'startAt' => $start_at,
                'maxResults' => $max_results,
                'jql' => $jql,
                'fields' => $fields,
                'expand' => $expand,
            )
        );
        return empty($result->issues) ? array() : $result->issues;
    }

    /**
     * @param array $keys
     *
     * @return \stdClass[]
     */
    public function getIssuesWithCommonBranches(array $keys)
    {
        $result = array();
        if (!empty($keys)) {
            $issues = $this->getIssuesFromJqlSearch(
                'Commits ~ "' . implode('_*" OR Commits ~ "', $keys) . '_*"'
            );

            foreach ($issues as $issue) {
                $result[$issue->key] = $issue;
            }
        }
        return $result;
    }

    public function assigneeIssue($issue_key, $person_name)
    {
        $this->_put("issue/{$issue_key}/assignee", ['name' => $person_name]);
    }

    public function reopenIssue($issue_key, $reason_id)
    {
        $this->progressIssue($issue_key, 'Reopen', false, ['customfield_11978' => ['id' => $reason_id]]);
    }

    public function progressIssue($issue, $step, $force = false, array $params = array())
    {
        if (is_string($issue)) {
            $issue = $this->getIssue($issue);
            // todo temporary. Remove (?) it from code after full switch to rest api
        } else {
            $issue = $this->getIssue($issue->key);
        }

        $old_params = $params;
        $params = [];
        foreach ($old_params as $field_id => $SoapParameter) {
            if (is_array($SoapParameter)) {
                // case of usage from new code
                $params[$field_id] = $SoapParameter;
                continue;
            }
            if (isset($SoapParameter->id)) {
                $field_id = $SoapParameter->id;
            }
            $value = $SoapParameter->values->enc_value;
            if (is_numeric($value)) {
                // seems like there is some problem with some of our custom fields when they got numeric values
                $params[$field_id] = ['value' => $value];
            } else {
                $params[$field_id] = $value;
            }
        }
        $actions = $this->getAvailableActions($issue->key, true);
        if (is_array($actions)) {
            foreach ($actions as $act) {
                if ($act->name === $step) {
                    $status_id = $this->getStatusIdByName($step);
                    if ($issue->fields->status->name != $step || $force) {
                        $issue->status = $status_id;

                        $params_keys = array_keys($params);
                        foreach ($params_keys as $key) {
                            if (!isset($act->fields->$key)) {
                                unset($params[$key]);
                            }
                        }

                        return $this->performTransition($issue->key, $act->id, $params);
                    }

                    throw new \Exception('Issue ' . $issue->key . ' already on \'' . $step . '\' step');
                }
            }
        }

        throw new \Exception(
            'Can\'t move issue \'' . $issue->key
                . '\' from \'' . $issue->fields->status->name . '\' to \'' . $step . '\''
                . ' (Workflow Transition with name \'' . $step . '\' not found.)'
        );
    }

    /**
     * Perform transition for issue.
     *
     * @param string $issue_key     - perform transition for this issue.
     * @param int    $transition_id - unique transition numeric ID.
     * @param array  $fields        - field values to be set during transition.
     * @param array  $update        - non-field changes (change worklog info, add comment, etc.)
     * @return null
     */
    public function performTransition($issue_key, $transition_id, $fields = [], $update = [])
    {
        $transition_data = ['transition' => ['id' => $transition_id]];
        if (!empty($fields)) {
            $transition_data['fields'] = $fields;
        }
        if (!empty($update)) {
            $transition_data['update'] = $update;
        }

        return $this->_post(
            "issue/{$issue_key}/transitions",
            $transition_data
        );
    }

    public function getStatusIdByName($status_name)
    {
        $all_statuses = $this->getStatuses();
        $status = array_filter(
            $all_statuses,
            function ($status) use ($status_name) {
                return $status->name === $status_name;
            }
        );
        $statuses_found = count($status);
        if ($statuses_found == 1) {
            $status = array_pop($status);
            return $status->id;
        } else {
            throw new \Exception("There are more than one statuses with name '{$status_name}': {$statuses_found}");
        }
    }

    public function getStatuses()
    {
        if (!isset($this->statuses)) {
            $this->statuses = $this->_get('status');
        }
        return $this->statuses;
    }

    /**
     * @param $id
     * @return \stdClass
     */
    public function getStatusById($id)
    {
        foreach ($this->getStatuses() as $status) {
            if ($status->id == $id) {
                return $status;
            }
        }
        return null;
    }

    public function getAvailableActions($issue_key, $expand_fields = false)
    {
        $request_data = [];
        if ($expand_fields) {
            $request_data = ['expand' => 'transitions.fields'];
        }
        $actions_info = $this->_get("issue/{$issue_key}/transitions", $request_data);
        return $actions_info->transitions;
    }

    public function getLinks($issue_key)
    {
        $issue_fields = $this->_get("issue/{$issue_key}")->fields;
        if (!isset($issue_fields->issuelinks)) {
            return [];
        }
        $linked_issues = [];
        foreach ($issue_fields->issuelinks as $issue) {
            $original_type = $issue->type;
            if (isset($issue->inwardIssue)) {
                $type = $original_type->inward;
                $issue = $issue->inwardIssue;
            } else {
                $type = $original_type->outward;
                $issue = $issue->outwardIssue;
            }
            $linked_issues[str_replace(' ', '_', strtolower($type))][] = ['issue' => $issue, 'type_info' => $original_type];
        }
        return $linked_issues;
    }

    public function getComments($issue_key)
    {
        return $this->_get("issue/{$issue_key}/comment");
    }

    /**
     * Look at https://docs.atlassian.com/jira/REST/latest/#api/2/user-findUsers for additional information
     * @param string $user             - search user pattern. Email, name, login, etc.
     * @param int    $start_at         - index of the first user to return.
     * @param int    $max_results      - maximum number of users to return.
     * @param bool   $include_active   - include active users to the results.
     * @param bool   $include_inactive - include inactive users to results.
     * @return array|null
     */
    public function searchUser($user, $start_at = 0, $max_results = 10, $include_active = true, $include_inactive = false)
    {
        return $this->_get(
            'user/search',
            [
                // actually: A query string used to search username, name or e-mail address
                'username'        => $user,
                'startAt'         => $start_at,
                'maxResults'      => $max_results,
                'includeActive'   => $include_active ? 'true' : 'false',
                'includeInactive' => $include_inactive ? 'true' : 'false',
            ]
        );
    }

    public function getUser($user_name)
    {
        if (!isset($this->users_cache[$user_name])) {
            $this->users_cache[$user_name] = $this->_get('user', ['username' => $user_name, 'expand' => 'groups']);
        }
        return $this->users_cache[$user_name];
    }

    public function getUserByKey($user_key)
    {
        return $this->_get('user', ['key' => $user_key, 'expand' => 'groups']);
    }

    public function getUserGroups($user_name)
    {
        return $this->getUser($user_name)->groups->items;
    }

    /**
     * @param $issue_key
     * @param $field_name
     * @return \stdClass|null|array
     */
    public function getIssueCustomFieldValue($issue_key, $field_name)
    {
        $Issue = $this->getIssue($issue_key);
        if (!$Issue) {
            return null;
        }
        return $this->getIssueCustomFieldValueByIssue($Issue, $field_name);
    }

    /**
     * @param \stdClass $Issue
     * @param $field_name
     * @return \stdClass|null|array
     */
    public function getIssueCustomFieldValueByIssue($Issue, $field_name)
    {
        $field_id = $this->getFieldId($field_name);
        if (isset($Issue->fields->$field_id)) {
            return $Issue->fields->$field_id;
        } else {
            return null;
        }
    }

    public function getAllFields()
    {
        if (!isset($this->all_fields)) {
            $this->all_fields = $this->_toArray($this->_get('field'));
        }
        return $this->all_fields;
    }

    public function getFieldId($field_name, $case_sensitive = false)
    {
        $field_info = $this->getFieldInfo($field_name, $case_sensitive);
        return !$field_info ? false : $field_info['id'];
    }

    public function getFieldInfo($field_name, $case_sensitive = false)
    {
        if (!$case_sensitive) $field_name = strtolower($field_name);

        foreach ($this->getAllFields() as $field_info) {
            $name = $case_sensitive ? $field_info['name'] : strtolower($field_info['name']);

            if ($name == $field_name) return $field_info;
        }

        return null;
    }

    /**
     * @param $issue_key
     * @param $update_info
     * $update_info example:
     * {
     *  "update": {
     *      "summary": [
     *          {
     *              "set": "Bug in business logic"
     *          }
     *      ],
     *      "timetracking": [
     *          {
     *              "edit": {
     *                  "originalEstimate": "1w 1d",
     *                  "remainingEstimate": "4d"
     *              }
     *          }
     *      ],
     *      "labels": [
     *          {
     *              "add": "triaged"
     *          },
     *          {
     *              "remove": "blocker"
     *          }
     *      ],
     *      "components": [
     *          {
     *              "set": ""
     *          }
     *      ]
     *  },
     *  "fields": {
     *      "summary": "This is a shorthand for a set operation on the summary field",
     *      "customfield_10010": 1,
     *      "customfield_10000": "This is a shorthand for a set operation on a text custom field"
     *  },
     */
    public function updateFields($issue_key, $update_info, $notify_users = true)
    {
        $update_request = [];
        foreach (['update', 'fields'] as $update_section) {
            if (!isset($update_info[$update_section])) {
                continue;
            }
            $fields_array = $update_info[$update_section];
            foreach ($fields_array as $field_name => $update_value) {
                $update_request[$update_section][$this->getFieldId($field_name) ? : $field_name] = $update_value;
            }
        }

        $update_request['notifyUsers'] = $notify_users;

        $this->_put("issue/{$issue_key}", $update_request);
    }

    private function _toArray($StdClass)
    {
        return json_decode(json_encode($StdClass), 1);
    }

    public function getWatchers($issue_key)
    {
        $Response = $this->_get("issue/{$issue_key}/watchers");
        if (!isset($Response->watchers)) {
            return [];
        } else {
            return array_map(
                function($Watcher) {
                    return $Watcher->key;
                },
                $Response->watchers
            );
        }
    }

    /**
     * Add new link of specific type between 2 issues selected by keys.
     *
     * @param string $link_type     - link type name (like 'PROJECT', 'Tests', etc)
     * @param string $in_issue_key  - first end of new link (link source, inward link end description).
     * @param string $out_issue_key - second end of new link (link destination, outward link end description).
     */
    public function linkIssues($link_type, $in_issue_key, $out_issue_key)
    {
        $request_data = null;
        $this->_post(
            "issueLink",
            $request_data = array(
                "type" => array("name" => $link_type),
                "inwardIssue" => array("key" => $in_issue_key),
                "outwardIssue" => array("key" => $out_issue_key)
            )
        );
    }

    /**
     * Remove link between to issues selected by key.
     *
     * @param string $link_type  - link type name
     * @param string $issue1_key - first end of link
     * @param string $issue2_key - second end of link
     *
     * @throws \Exception
     */
    public function unlinkIssues($link_type, $issue1_key, $issue2_key)
    {
        $link_type = strtolower($link_type);
        $issue1_data = $this->getIssue($issue1_key);
        $links = $issue1_data->fields->issuelinks;

        if (!empty($links)) {
            foreach ($links as $Link) {
                $type = strtolower($Link->type->name);
                $linked_issue = isset($Link->inwardIssue) ? $Link->inwardIssue : $Link->outwardIssue;

                if ($linked_issue->key == $issue2_key && $link_type == $type) {
                    $this->removeLink($Link->id);
                    break;
                }
            }
        }
    }

    /**
     * Remove link between issues by ID.
     *
     * @param int $link_id - link ID
     *
     * @throws \Exception
     */
    public function removeLink($link_id)
    {
        $this->_delete("issueLink/{$link_id}");
    }

    public function addWatcher($issue_key, $new_watcher)
    {
        $this->_post("issue/{$issue_key}/watchers", $new_watcher);
    }

    public function addComment($issue_key, $comment)
    {
        return $this->_post("issue/{$issue_key}/comment", ['body' => $comment]);
    }

    public function updateComment($issue_key, $comment_id, $new_text)
    {
        return $this->_put("issue/{$issue_key}/comment/{$comment_id}", ['body' => $new_text]);
    }

    /**
     * @param string $issue_key - key of issue to attach files to.
     * @param string $file_path - path to file to upload to Jira as attachment to an issue.
     * @param string $file_type - file's mime type
     * @param string $file_name - name of file to be sent to Jira.
     *
     * @return array|null
     */
    public function attachFileToIssue($issue_key, $file_path, $file_type = null, $file_name = null)
    {
        $File = new \CURLFile($file_path, $file_type, $file_name);
        return $this->_multipart("issue/{$issue_key}/attachments", ['file' => $File]);
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
     * Make a request to Jira REST API and parse response.
     * Return Array with response data or null for empty response body.
     *
     * @param string $method   - HTTP request method (e.g. HEAD/PUT/GET...)
     * @param string $command  - API method path (e.g. issue/<key>)
     * @param array $arguments - request data (parameters)
     *
     * @return \stdClass|\stdClass[]|null      - array (parsed response JSON) or null (on 204 response code with empty body) for
     *                           successful request.
     *
     * @throws \Exception - on JSON parse errors, on warning HTTP codes and other errors.
     */
    private function _request($method, $command, $arguments = [])
    {
        $url = $this->jira_url . self::REST_URL . $command;
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
            'X-Atlassian-Token' => 'nocheck',
        ];

        if (\GitPHP\Config::GetInstance()->GetAuthMethod() == \GitPHP\Config::AUTH_METHOD_JIRA) {
            //auth by crowd auth token got via rest authorisation
            $User = \GitPHP\Session::instance()->getUser();
            if (!empty($User) && !empty($User->getToken())) {
                $header_options['Cookie'] = \GitPHP\Jira::REST_COOKIE_NAME . '=' . $User->getToken();
            }
        }
        if (empty($header_options['Cookie'])) {
            //try to auth by AUTH details provided
            $curl_options[CURLOPT_USERPWD] = $this->jira_user . ':' . $this->jira_password;
        }

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
            return null;  // sometimes Jira returns response with empty body (e.g. for 201, 204 codes).
        }

        $result = json_decode($result);
        $error = json_last_error();
        if (JSON_ERROR_NONE !== $error) {
            throw new \Exception(json_last_error_msg());
        }
        if (isset($result->errorMessages) && !empty($result->errorMessages)) {
            throw new \Exception("Errors occurred when performed api call: " . implode('; ', $result->errorMessages));
        }
        if ($http_code > 300) {
            throw new \Exception("Unknown error occurred when tried to perform api call. API answer: " . var_export($result, 1));
        }
        return $result;
    }
}
