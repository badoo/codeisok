<?php

namespace GitPHP\Git;

class ProjectListArray extends \GitPHP\Git\ProjectListBase
{
    /**
     * __construct
     *
     * constructor
     *
     * @param mixed $projectArray array to read
     * @throws \Exception if parameter is not an array
     * @access public
     */
    public function __construct($projectArray)
    {
        if (!is_array($projectArray)) {
            throw new \Exception('An array of projects is required');
        }

        $this->projectConfig = $projectArray;

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
        foreach ($this->projectConfig as $proj => $projData) {
            try {
                if (is_string($projData)) {
                    // Just flat array of project paths
                    $projObj = new \GitPHP\Git\Project($projData);
                    $this->projects[$projData] = $projObj;
                } else if (is_array($projData)) {
                    if (is_string($proj) && !empty($proj)) {
                        // Project key pointing to data array
                        $projObj = new \GitPHP\Git\Project($proj);
                        $this->projects[$proj] = $projObj;
                        $this->ApplyProjectSettings($proj, $projData);
                    } else if (isset($projData['project'])) {
                        // List of data arrays with projects inside
                        $projObj = new \GitPHP\Git\Project($projData['project']);
                        $this->projects[$projData['project']] = $projObj;
                        $this->ApplyProjectSettings(null, $projData);
                    }
                }
            } catch (\Exception $e) {
                \GitPHP\Log::GetInstance()->Log(__METHOD__, $e->__toString());
            }
        }
    }
}
