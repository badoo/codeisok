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
    protected static $instance;

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
    public static function Instantiate()
    {
        if (self::$instance) return;

        $git_projects = array();
        $git_projects_settings = array();
        $ModelGitosis = new \GitPHP\Model_Gitosis();
        foreach ($ModelGitosis->getRepositories(true) as $project) {
            $git_projects[] = $project['project'];
            $git_projects_settings[$project['project']] = array(
                'description' => $project['description'],
                'category' => $project['category'],
                'notify_email' => $project['notify_email'],
            );
        }

        self::$instance = new \GitPHP\Git\ProjectListArray($git_projects);
        self::$instance->ApplySettings($git_projects_settings);
    }
}

