<?php
class GitPHP_Config
{
    const PROJECT_ROOT = 'projectroot';

    /** Authentication methods that are supported. Change method in .config/gitphp.conf.php file */
    const AUTH_METHOD         = 'auth_method';
    const AUTH_METHOD_NONE    = 'none';
    const AUTH_METHOD_CROWD   = 'crowd';
    const AUTH_METHOD_JIRA    = 'jira';
    const AUTH_METHOD_REDMINE = 'redmine';
    const AUTH_METHOD_CONFIG  = 'config';

    // User-Password to use with AUTH_METHOD_CONFIG
    const CONFIG_AUTH_USER = 'config_auth_user';

    // DB options
    const DB_HOST                      = 'localhost';
    const DB_USER                      = 'username';
    const DB_PASSWORD                  = 'userpass';
    const DB_NAME                      = 'dbname';

    // Access options
    const CHECK_ACCESS_GROUP           = false;
    const ACCESS_GROUP                 = 'access_group';
    const PROJECT_ACCESS_GROUPS        = 'project_access_groups';
    const GIT_NO_AUTH_ACTIONS          = 'git_no_auth_actions';
    const GIT_USER                     = 'git';
    const GIT_HOME                     = '/home/git/';

    //Review options
    const USE_JIRA                     = false;
    const USE_REDMINE                  = true;

    // Others
    const COLLECT_CHANGES_AUTHORS      = 'collect_changes_authors';
    const COLLECT_CHANGES_AUTHORS_SKIP = 'collect_changes_authors_skip';
    const HIDE_FILES_PER_CATEGORY      = 'hide_files_per_category';
    const SKIP_SUPPRESS_FOR_CATEGORY   = 'skip_suppress_for_category';
    const DEBUG_ENABLED                = true;

    //static
    const STATIC_VERSION_CSS           = '1';
    const STATIC_VERSION_JS            = '1';

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
     * @return GitPHP_Config
     */
    public static function GetInstance()
    {
        if (!self::$instance) {
            self::$instance = new GitPHP_Config();
        }
        return self::$instance;
    }

    /**
     * LoadConfig
     *
     * Loads a config file
     *
     * @access public
     * @param string $configFile config file to load
     * @throws Exception on failure
     */
    public function LoadConfig($configFile)
    {
        if (!is_file($configFile)) {
            throw new GitPHP_MessageException('Could not load config file ' . $configFile, true, 500);
        }

        if (($gitphp_conf = include($configFile)) === false) {
            throw new GitPHP_MessageException('Could not read config file ' . $configFile, true, 500);
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
     * @return array
     */
    public function GetAuthUser()
    {
        if ($this->GetAuthMethod() !== self::AUTH_METHOD_CONFIG) {
            return [];
        }
        return $this->GetValue(self::CONFIG_AUTH_USER);
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
}
