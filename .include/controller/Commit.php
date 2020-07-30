<?php
namespace GitPHP\Controller;

class Commit extends Base
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
        if (isset($this->params['jstip']) && $this->params['jstip']) {
            return 'committip.tpl';
        }
        return 'commit.tpl';
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
        return $this->params['hash'];
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
            return __('commit');
        }
        return 'commit';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    protected function ReadQuery()
    {
        if (isset($_GET['h'])) $this->params['hash'] = $_GET['h'];
        else $this->params['hash'] = 'HEAD';

        if (isset($_GET['o']) && ($_GET['o'] == 'jstip')) {
            $this->params['jstip'] = true;
            \GitPHP\Log::GetInstance()->SetEnabled(false);
        }
        $this->params['retbranch'] = isset($_GET['retbranch']) ? $_GET['retbranch'] : null;
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    protected function LoadData()
    {
        $commit = $this->project->GetCommit($this->params['hash']);
        $this->tpl->assign('commit', $commit);
        $this->tpl->assign('tree', $commit->GetTree());
        $treediff = $commit->DiffToParent();
        $treediff->SetRenames(true);
        $this->tpl->assign('treediff', $treediff);
        $this->tpl->assign('retbranch', $this->params['retbranch']);
    }
}
