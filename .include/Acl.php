<?php

namespace GitPHP;

class Acl
{
    const CONF_PROJECT_ACCESS_GROUPS_KEY = \GitPHP_Config::PROJECT_ACCESS_GROUPS;

    const CONF_ACCESS_GROUP_KEY = \GitPHP_Config::ACCESS_GROUP;

    const GITOSIS_ADMIN_GROUP = 'gitosis-admin';

    /** @var static */
    static protected $instance;

    /** @var Jira */
    protected $Jira;

    /**
     * @return static
     */
    static public function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static(\GitPHP\Jira::instance());
        }

        return static::$instance;
    }

    public function __construct($Jira)
    {
        $this->Jira = $Jira;
    }

    public function isGitosisAdmin(\GitPHP_User $User)
    {
        return $this->isGroupMemberCached(self::GITOSIS_ADMIN_GROUP, $User);
    }

    public function isCodeAccessAllowed(\GitPHP_User $User)
    {
        if (empty($User->getId())) {
            return false;
        }

        return $this->isGroupMemberCached(\GitPHP_Config::GetInstance()->GetValue(self::CONF_ACCESS_GROUP_KEY), $User);
    }

    /**
     * @param string $project
     * @param \GitPHP_User $User
     * @return bool
     */
    public function isProjectAllowed($project, \GitPHP_User $User)
    {
        $project_access_groups = \GitPHP_Config::GetInstance()->GetValue(self::CONF_PROJECT_ACCESS_GROUPS_KEY);
        if (!is_array($project_access_groups) || empty($project_access_groups[$project])) {
            return true;
        }
        if (empty($User->getId())) {
            return false;
        }
        $groups = $project_access_groups[$project];

        if (!is_array($groups)) $groups = [$groups];

        foreach ($groups as $group_name) {
            if ($this->isGroupMemberCached($group_name, $User)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check User for permission to perform a specific action on specific project (repository).
     * @param \GitPHP_Project   $Project - project to check
     * @param string            $action  - action to check
     * @param \GitPHP_User|null $User    - user to check for permission.
     *                                     When 'null' is given - current (authenticated) user is used.
     *
     * @return bool
     */
    public function isActionAllowed($Project, $action, $User = null)
    {
        if (!isset($User)) $User = \GitPHP_Session::instance()->getUser();

        if (empty($User->getId())) {
            return in_array($action, \GitPHP_Config::GetInstance()->GetGitNoAuthActions($Project->GetProject()));
        } else {
            return $this->isProjectAllowed($Project->GetProject(), $User);
        }
    }

    protected function isGroupMemberCached($group_name, \GitPHP_User $User)
    {
        $is_in_group = $User->isInGroup($group_name);
        if ($is_in_group === null) {
            $is_in_group = false;
            if (\GitPHP_Config::AUTH_METHOD['crowd']) {
                $is_in_group = $this->Jira->crowdIsGroupMember($User->getId(), $group_name);
            } elseif (\GitPHP_Config::AUTH_METHOD['jira']) {
                $is_in_group = $this->Jira->restIsGroupMember($User->getId(), $group_name);
            } elseif (\GitPHP_Config::AUTH_METHOD['config']) {
                $is_in_group = \GitPHP_Config::AUTH_USER['admin'];
            }
            $User->setInGroup($group_name, $is_in_group);
        }
        return $is_in_group;
    }
}
