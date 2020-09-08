<?php

namespace GitPHP\Git;

class ProjectListArray extends \GitPHP\Git\ProjectListBase
{
    /**
     * __construct
     *
     * constructor
     *
     * @param string[] $project_array array to read
     * @throws \Exception if parameter is not an array
     * @access public
     */
    public function __construct($project_array)
    {
        if (!is_array($project_array)) {
            throw new \Exception('An array of projects is required');
        }

        $this->projectConfig = $project_array;

        parent::__construct();
    }

    /**
     * PopulateProjects
     *
     * Populates the internal list of projects
     *
     * @access protected
     * @throws \Exception if file cannot be read
     */
    protected function PopulateProjects()
    {
        foreach ($this->projectConfig as $project) {
            $this->projects[$project] = $project;
        }
    }

    /**
     * GetProject
     *
     * Gets a particular project
     *
     * @access public
     * @param string $project the project to find
     *
     * @return \GitPHP\Git\Project mixed project object or null
     * @throws \Exception
     */
    public function GetProject($project)
    {
        if (empty($project)) {
            return null;
        }

        if (isset($this->projects[$project])) {
            if (is_string($this->projects[$project])) {
                try {
                    $ProjectObject = new \GitPHP\Git\Project($project);
                } catch (\Exception $e) {
                    unset($this->projects[$project]);
                    return null;
                }
                // unfortunately we need to set this early because ApplyProjectSettings uses it
                $this->projects[$project] = $ProjectObject;

                $this->ApplyProjectSettings($project, $this->projectSettings[$project]);
            }
            return $this->projects[$project];
        }

        return null;
    }
}
