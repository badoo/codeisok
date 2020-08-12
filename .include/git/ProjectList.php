<?php

namespace GitPHP\Git;

class ProjectList
{
    /**
     * instance
     *
     * Stores the singleton instance of the projectlist
     *
     * @access protected
     * @static
     */
    protected static $instance = null;

    /**
     * GetInstance
     *
     * Returns the singleton instance
     *
     * @access public
     * @static
     * @return \GitPHP\Git\ProjectListBase mixed instance of projectlist
     * @throws \Exception if projectlist has not been instantiated yet
     */
    public static function GetInstance()
    {
        return self::$instance;
    }

    /**
     * Instantiate
     *
     * Instantiates the singleton instance
     *
     * @access private
     * @static
     * @param string $file config file with git projects
     * @param boolean $legacy true if this is the legacy project config
     * @throws \Exception if there was an error reading the file
     */
    public static function Instantiate($file = null, $legacy = false)
    {
        if (self::$instance) return;

        if (!empty($file) && is_file($file) && include($file)) {
            if (isset($git_projects)) {
                if (is_string($git_projects)) {
                    self::$instance = new \GitPHP\Git\ProjectListFile($git_projects);
                } else if (is_array($git_projects)) {
                    if ($legacy) {
                        self::$instance = new \GitPHP\Git\ProjectListArrayLegacy($git_projects);
                    } else {
                        self::$instance = new \GitPHP\Git\ProjectListArray($git_projects);
                    }
                }
            }
        }

        if (!self::$instance) self::$instance = new \GitPHP\Git\ProjectListDirectory(\GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::PROJECT_ROOT));

        if (isset($git_projects_settings) && !$legacy) self::$instance->ApplySettings($git_projects_settings);
    }
}

