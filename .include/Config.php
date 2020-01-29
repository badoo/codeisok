<?php

namespace GitPHP;

class Config
{
    const PROJECT_ROOT = 'projectroot';

    // Authentication methods that are supported. Change method in .config/gitphp.conf.php file
    const AUTH_METHOD = 'auth_method';
    const AUTH_METHOD_NONE = 'none';
    const AUTH_METHOD_CROWD = 'crowd';
    const AUTH_METHOD_JIRA = 'jira';
    const AUTH_METHOD_REDMINE = 'redmine';
    const AUTH_METHOD_CONFIG = 'config';

    // User-Password to use with AUTH_METHOD_CONFIG
    const CONFIG_AUTH_USER = 'config_auth_user';

    // experimental: you can specify tokens to access codeisok's API from outside
    const AUTH_API_TOKENS = "auth_api_tokens";

    // DB options
    const DB_HOST = 'localhost';
    const DB_USER = 'username';
    const DB_PASSWORD = 'userpass';
    const DB_NAME = 'dbname';

    // Jira options
    const JIRA_URL = 'jira_url';
    const JIRA_USER = 'jira_user';
    const JIRA_PASSWORD = 'jira_password';

    // Crowd options
    const CROWD_URL        = 'crowd_url';
    const CROWD_APP_TOKEN  = 'crowd_token';
    const CROWD_SSO_DOMAIN = 'crowd_domain';

    // Access options
    const CHECK_ACCESS_GROUP = 'check_access_group';
    const ACCESS_GROUP = 'access_group';
    const PROJECT_ACCESS_GROUPS = 'project_access_groups';
    const GIT_NO_AUTH_ACTIONS = 'git_no_auth_actions';
    const GIT_USER = 'git';
    const GIT_HOME = '/home/git/';

    // Git access options
    const UPDATE_AUTH_KEYS_FROM_WEB = 'update_auth_keys_from_web';
    const ALLOW_USER_CREATE_REPOS = 'allow_user_create_repos';

    // Tracker options
    const TRACKER_TYPE = 'tracker_type';
    const TRACKER_TYPE_JIRA = \GitPHP\Tracker::TRACKER_TYPE_JIRA;
    const TRACKER_TYPE_REDMINE = \GitPHP\Tracker::TRACKER_TYPE_REDMINE;

    // Review options
    const COLLECT_CHANGES_AUTHORS = 'collect_changes_authors';
    const COLLECT_CHANGES_AUTHORS_SKIP = 'collect_changes_authors_skip';
    const HIDE_FILES_PER_CATEGORY = 'hide_files_per_category';
    const BASE_BRANCHES_PER_CATEGORY = 'base_branches_per_category';
    const SKIP_SUPPRESS_FOR_CATEGORY = 'skip_suppress_for_category';
    const IGNORED_EMAIL_ADDRESSES = 'ignored_email_addresses';
    const FORCE_BASE_BRANCH_PER_REPOSITORY = 'force_base_branches';
    const BUILD_BRANCH_PATTERN = 'build_branch_pattern';
    const LARGE_DIFF_SIZE = 'large_diff_size';

    // Debug
    const DEBUG_ENABLED = true;

    /**
     * instance
     *
     * Stores the singleton instance
     *
     * @access protected
     * @static
     */
    protected static $instance;

    /**
     * values
     *
     * Stores the config values
     *
     * @access protected
     */
    protected $values = array();

    /**
     * configs
     *
     * Stores the config files
     *
     * @access protected
     */
    protected $configs = array();

    /**
     * GetInstance
     *
     * Returns the singleton instance
     *
     * @access public
     * @static
     * @return Config
     */
    public static function GetInstance()
    {
        if (!self::$instance) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public function IsCli()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * LoadConfig
     *
     * Loads a config file
     *
     * @access public
     * @param string $configFile config file to load
     * @throws \Exception on failure
     */
    public function LoadConfig($configFile)
    {
        if (!is_file($configFile)) {
            throw new \GitPHP_MessageException('Could not load config file ' . $configFile, true, 500);
        }

        /** @noinspection PhpIncludeInspection */
        if (($gitphp_conf = include($configFile)) === false) {
            throw new \GitPHP_MessageException('Could not read config file ' . $configFile, true, 500);
        }
        if (is_array($gitphp_conf)) {
            $this->values = array_merge($this->values, $gitphp_conf);
        }

        $this->configs[] = $configFile;
    }

    /**
     * ClearConfig
     *
     * Clears all config values
     *
     * @access public
     */
    public function ClearConfig()
    {
        $this->values = array();
        $this->configs = array();
    }

    /**
     * Gets a config value
     *
     * @access public
     * @param mixed $key config key to fetch
     * @param mixed $default default config value to return
     * @return mixed config value
     */
    public function GetValue($key, $default = null)
    {
        if ($this->HasKey($key)) {
            return $this->values[$key];
        }
        return $default;
    }

    /**
     * SetValue
     *
     * Sets a config value
     *
     * @access public
     * @param string $key config key to set
     * @param mixed $value value to set
     */
    public function SetValue($key, $value)
    {
        if (empty($key)) {
            return;
        }
        if (empty($value)) {
            unset($this->values[$key]);
            return;
        }
        $this->values[$key] = $value;
    }

    /**
     * HasKey
     *
     * Tests if the config has specified this key
     *
     * @access public
     * @param string $key config key to find
     * @return boolean true if key exists
     */
    public function HasKey($key)
    {
        if (empty($key)) {
            return false;
        }
        return isset($this->values[$key]);
    }

    /* *****
     * Specific custom getters for configuration options.
     * *****/

    /**
     * Get tracker type to use
     * @return string
     */
    public function GetTrackerType()
    {
        return $this->GetValue(self::TRACKER_TYPE);
    }

    /**
     * Should use jira tracker
     * @return bool
     */
    public function GetUseJiraTracker()
    {
        return $this->GetTrackerType() === self::TRACKER_TYPE_JIRA;
    }

    /**
     * Should use redmine tracker
     * @return bool
     */
    public function GetUseRedmineTracker()
    {
        return $this->GetTrackerType() === self::TRACKER_TYPE_REDMINE;
    }

    /**
     * Get crowd instance url
     *
     * @return string
     */
    public function GetCrowdUrl()
    {
        return $this->GetValue(self::CROWD_URL, '');
    }

    /**
     * Get crowd application token
     *
     * @return string
     */
    public function GetCrowdToken()
    {
        return $this->GetValue(self::CROWD_APP_TOKEN, '');
    }

    /**
     * Get jira instance url
     *
     * @return string
     */
    public function GetJiraUrl()
    {
        return $this->GetValue(self::JIRA_URL, '');
    }

    /**
     * Get jira user
     *
     * @return string
     */
    public function GetJiraUser()
    {
        return $this->GetValue(self::JIRA_USER, '');
    }

    /**
     * Get jira password
     *
     * @return string
     */
    public function GetJiraPassword()
    {
        return $this->GetValue(self::JIRA_PASSWORD, '');
    }

    /**
     * Get auth method that we should use
     * One of the self::AUTH_METHOD_* constants
     *
     * @return string
     */
    public function GetAuthMethod()
    {
        return $this->GetValue(self::AUTH_METHOD, self::AUTH_METHOD_NONE);
    }

    /**
     * Get user credentials that should be used with AUTH_METHOD_CONFIG auth method
     *
     * @param $username
     * @return array
     */
    public function GetAuthUserByName($username) : array
    {
        if ($this->GetAuthMethod() !== self::AUTH_METHOD_CONFIG) {
            return [];
        }
        $users = $this->GetValue(self::CONFIG_AUTH_USER);
        $old_config_format = !empty($users) &&  is_string(reset($users));
        if ($old_config_format) {
            return $users['name'] === $username ? $users : [];
        }

        return $users[$username] ?? [];
    }

    /**
     * Get list of actions, allowed for project without authentication.
     * @param string $project - project (repository) name.
     * @return string[] - list of actions allowed.
     */
    public function GetGitNoAuthActions($project)
    {
        $git_no_auth_actions = $this->GetValue(self::GIT_NO_AUTH_ACTIONS, []);

        $allowed_by_default  = $git_no_auth_actions['default'] ?? [];
        $allowed_for_project = $git_no_auth_actions[$project]  ?? [];

        return array_merge($allowed_by_default, $allowed_for_project);
    }

    /**
     * Get base branches for review. By default it's master.
     *
     * @param $category
     * @return array
     */
    public function GetBaseBranchesByCategory($category)
    {
        $base_branches_per_category = $this->GetValue(static::BASE_BRANCHES_PER_CATEGORY, []);
        return $base_branches_per_category[$category] ?? ['master'];
    }

    /**
     * Get user data by API token provided. Experimental method to support external requests to our API
     *
     * @param $token
     * @return bool
     */
    public function GetUserDataByApiToken($token)
    {
        $tokens_list = $this->GetValue(self::AUTH_API_TOKENS, []);
        return $tokens_list[$token] ?? false;
    }
}
