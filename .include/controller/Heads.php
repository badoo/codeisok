<?php
namespace GitPHP\Controller;

class Heads extends Base
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->project) {
            throw new \GitPHP\MessageException(__('Project is required'), true);
        }
    }

    /**
     * GetTemplate
     *
     * Gets the template for this controller
     *
     * @access protected
     * @return string template filename
     */
    protected function GetTemplate()
    {
        return 'heads.tpl';
    }

    /**
     * GetCacheKey
     *
     * Gets the cache key for this controller
     *
     * @access protected
     * @return string cache key
     */
    protected function GetCacheKey()
    {
        return '';
    }

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @access public
     * @param boolean $local true if caller wants the localized action name
     * @return string action name
     */
    public function GetName($local = false)
    {
        if ($local) {
            return __('heads');
        }
        return 'heads';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    protected function ReadQuery() {}

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    protected function LoadData()
    {
        $head = $this->project->GetHeadCommit();
        $this->tpl->assign("head", $head);

        $head_list = $this->project->GetHeads();
        if (isset($head_list) && (count($head_list) > 0)) {
            $this->tpl->assign("headlist", $head_list);
        }
    }
}
