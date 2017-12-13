<?php

namespace GitPHP;

class Tracker
{
    const JIRA_TICKET_REGEXP        = '#(?P<ticket>[A-Z]+\-[0-9]+)#';
    const REDMINE_TICKET_REGEXP     = '#issue\-(?P<ticket>[0-9]+)#';

    const TRACKER_TYPE_DISABLED     = '';
    const TRACKER_TYPE_JIRA         = 'Jira';
    const TRACKER_TYPE_REDMINE      = 'Redmine';

    protected static $instance;

    protected $tracker_type = self::TRACKER_TYPE_DISABLED;

    public static function instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $res = self::TRACKER_TYPE_DISABLED;
        if (\GitPHP_Config::GetInstance()->GetUseJiraTracker()) {
            $res = self::TRACKER_TYPE_JIRA;
        } elseif (\GitPHP_Config::GetInstance()->GetUseRedmineTracker()) {
            $res = self::TRACKER_TYPE_REDMINE;
        }
        $this->tracker_type = $res;
    }

    public function enabled()
    {
        return $this->getTrackerType() !== self::TRACKER_TYPE_DISABLED;
    }

    public function getTrackerType()
    {
        return $this->tracker_type;
    }

    public function getTicketRegexp()
    {
        $res = '';
        switch ($this->tracker_type) {
            case self::TRACKER_TYPE_JIRA:
                $res = self::JIRA_TICKET_REGEXP;
                break;
            case self::TRACKER_TYPE_REDMINE:
                $res = self::REDMINE_TICKET_REGEXP;
                break;
        }
        return $res;
    }

    public function getTicketUrl($ticket_key)
    {
        $url = '';
        switch ($this->tracker_type) {
            case self::TRACKER_TYPE_JIRA:
                $url = \GitPHP_Config::GetInstance()->GetJiraUrl() . 'browse/' . $ticket_key;
                break;
            case self::TRACKER_TYPE_REDMINE:
                $url = \GitPHP\Redmine::URL . 'issues/' . $ticket_key;
                break;
        }
        return $url;
    }

    public function parseTicketFromString($string)
    {
        $key = '';
        if (preg_match($this->getTicketRegexp(), $string, $m)) {
            $key = $m['ticket'];
        }
        return $key;
    }

    public function getReviewTicketPrefix()
    {
        switch($this->tracker_type) {
            case self::TRACKER_TYPE_REDMINE:
                return 'issue-';
            default:
                return '';
        }
    }

    public function getTicketSummary($ticket_key)
    {
        $summary = '';
        switch ($this->tracker_type) {
            case self::TRACKER_TYPE_JIRA:
                if ($Ticket = \GitPHP\JiraRestClient::getInstance()->getIssue($ticket_key)) {
                    if (isset($Ticket->fields->summary)) {
                        $summary = $Ticket->fields->summary;
                    }
                }
                break;
            case self::TRACKER_TYPE_REDMINE:
                $Issue = \GitPHP\RedmineRestClient::getInstance()->getIssue($ticket_key);
                if (!empty($Issue->issue) && !empty($Issue->issue->subject)) {
                    $summary = $Issue->issue->subject;
                }
                break;
        }
        return $summary;
    }

    public function getTicketDeveloperEmail($ticket_key)
    {
        $res = '';
        switch ($this->tracker_type) {
            case self::TRACKER_TYPE_JIRA:
                $Developer = \GitPHP\JiraRestClient::getInstance()->getIssueCustomFieldValue($ticket_key, 'Developer');
                if (!empty($Developer) && !empty($Developer->emailAddress)) {
                    $res = $Developer->emailAddress;
                }
                break;
            case self::TRACKER_TYPE_REDMINE:
                $Issue = \GitPHP\RedmineRestClient::getInstance()->getIssue($ticket_key);
                if (!empty($Issue->issue) && !empty($Issue->issue->assigned_to) && !empty($Issue->issue->assigned_to->id)) {
                    $developer_id = $Issue->issue->assigned_to->id;
                    $Developer = \GitPHP\RedmineRestClient::getInstance()->getUser($developer_id);
                    if (!empty($Developer) && !empty($Developer->user) && !empty($Developer->user->mail)) {
                        $res = $Developer->user->mail;
                    }
                }
                break;
        }
        return $res;
    }

    public function getUserEmail($user)
    {
        $res = '';
        switch ($this->tracker_type) {
            case self::TRACKER_TYPE_JIRA:
                $JiraUser = \GitPHP\JiraRestClient::getInstance()->getUser($user);
                if (!empty($JiraUser) && !empty($JiraUser->emailAddress)) {
                    $res = $JiraUser->emailAddress;
                }
                break;
            case self::TRACKER_TYPE_REDMINE:
                $RedmineUsers = \GitPHP\RedmineRestClient::getInstance()->searchUserByName($user);
                if (!empty($RedmineUsers) && !empty($RedmineUsers->users && !empty($RedmineUsers->users[0])) && !empty($RedmineUsers->users[0]->mail)) {
                    $res = $RedmineUsers->users[0]->mail;
                }
                break;
        }
        return $res;
    }
    
    public function getCommentsFormat()
    {
        $format = 'html';
        if ($this->getTrackerType() == self::TRACKER_TYPE_JIRA) {
            $format = 'jira';
        } elseif ($this->getTrackerType() == self::TRACKER_TYPE_REDMINE) {
            $format = 'redmine';
        }
        return $format;
    }

    public function addComment($ticket_key, $comment)
    {
        switch ($this->tracker_type) {
            case self::TRACKER_TYPE_JIRA:
                \GitPHP\JiraRestClient::getInstance()->addComment($ticket_key, $comment);
                break;
            case self::TRACKER_TYPE_REDMINE:
                \GitPHP\RedmineRestClient::getInstance()->addComment($ticket_key, $comment);
                break;
        }
    }
}
