<?php
namespace GitPHP\Controller;

class Log extends Base
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
        if (isset($this->params['short']) && ($this->params['short'] === true)
            || isset($this->params['branchlog']) && ($this->params['branchlog'] === true)) {
            return 'shortlog.tpl';
        }
        return 'log.tpl';
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
        return $this->params['hash'] . '|' . $this->params['page'] . '|' . (isset($this->params['mark']) ? $this->params['mark'] : '');
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
        if (isset($this->params['branchlog'])) {
            return 'branchlog';
        }
        if (isset($this->params['short']) && ($this->params['short'] === true)) {
            if ($local) {
                return __('shortlog');
            }
            return 'shortlog';
        }
        if ($local) {
            return __('log');
        }
        return 'log';
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
        $hash = 'HEAD';
        if (isset($_GET['h']) && $_GET['h'] !== 'refs/heads/HEAD') {
            $hash = $_GET['h'];
        }

        $this->params['hash'] = $hash;

        if (isset($_GET['pg'])) $this->params['page'] = $_GET['pg'];
        else $this->params['page'] = 0;
        if (isset($_GET['m'])) $this->params['mark'] = $_GET['m'];
        $this->params['branchlog'] = !empty($_GET['branchlog']);
        $this->params['base'] = $this->Session->get($this->project->GetProject() . \GitPHP\Session::SESSION_BASE_BRANCH, '');
        if (isset($_REQUEST['base'])) {
            $this->params['base'] = $_REQUEST['base'];
            $this->Session->set($this->project->GetProject() . \GitPHP\Session::SESSION_BASE_BRANCH, $this->params['base']);
        }
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
        $co = $this->project->GetCommit($this->params['hash']);
        $toHash = null;
        if (!$co) {
            $co = \GitPHP\Db::getInstance()->getBranchHead($this->project->GetProject(), $this->params['hash']);
            if ($co) $co = $this->project->GetCommit($co);
            if (!$co) return;
            $toHash = $co->GetHash();
        }
        \GitPHP\Log::GetInstance()->Log(__METHOD__, is_object($co) ? get_class($co) : var_export($co, true));
        $this->tpl->assign('commit', $co);
        $this->tpl->assign('head', $this->project->GetHeadCommit());
        $this->tpl->assign('page', $this->params['page']);
        $this->tpl->assign('controller', $this->params['branchlog'] ? 'branchlog' : 'shortlog');

        $branch_name = '';
        if (strpos($this->params['hash'], 'refs/heads/') === 0) {
            $branch_name = substr($this->params['hash'], 11);
            $this->tpl->assign('branch_name', $branch_name);
        } else if (!preg_match('#^[0-9a-z]{40}$#', $this->params['hash'])) {
            $this->tpl->assign('branch_name', $this->params['hash']);
        }

        if (isset($this->params['branchlog']) && $this->params['branchlog']) {
            $this->tpl->assign('enablesearch', false);
            $this->tpl->assign('enablebase', true);
            $this->tpl->assign('base', $this->params['base']);
            $base_branches = $this->project->GetBaseBranches($branch_name);
            $this->tpl->assign('base_branches', $base_branches);

            $BranchDiff = new \GitPHP\Git\BranchDiff($this->project, $this->params['hash'], $this->params['base'], new \GitPHP\Git\DiffContext());
            $BranchDiff->SetToHash($toHash);
            $hashBase = $BranchDiff->getBaseHash();
        } else {
            $hashBase = null;
        }

        $revlist = $this->project->GetLog($toHash ? $toHash : $this->params['hash'], 101, ($this->params['page'] * 100), $hashBase);
        if ($revlist) {
            if (count($revlist) > 100) {
                $this->tpl->assign('hasmorerevs', true);
                $revlist = array_slice($revlist, 0, 100);
            }
            $revlist_hashes = array();
            $revlist_index = array();
            /** @var $revlist \GitPHP\Git\Commit[] */
            foreach ($revlist as $idx => $commit) {
                $revlist_hashes[] = $commit->GetHash();
                $revlist_index[$commit->GetHash()] = $idx;
            }
            foreach($revlist_hashes as $revlist_hash) {
                $reviews = \GitPHP\Db::getInstance()->findSnapshotsByHash($revlist_hash);
                foreach ($reviews as $hash => $review) {
                    $revlist[$revlist_index[$hash]]->setReview($review);
                }
            }

            $this->tpl->assign('revlist', $revlist);
        }

        $this->tpl->assign('mark', isset($this->params['mark']) ? $this->project->GetCommit($this->params['mark']) : null);
    }
}
