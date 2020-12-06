<?php
namespace GitPHP\Controller;

class Git extends Base
{
    const SUPPORTED_ACTIONS = [
        self::ACTION_MONITORING,
        self::ACTION_BRANCH_HEAD,
        self::ACTION_TAG_HEAD,
        self::ACTION_MERGE_BASE,
        self::ACTION_ONELINE_LOG,
    ];

    const ACTION_MONITORING  = 'monitoring';
    const ACTION_BRANCH_HEAD = 'branch-head';
    const ACTION_TAG_HEAD    = 'tag-head';
    const ACTION_MERGE_BASE  = 'merge-base';
    const ACTION_ONELINE_LOG = 'oneline-log';

    protected $action;

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
        return 'git.tpl';
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
        return null;
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
        return $this->action ?? 'git';
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
        $this->action = $this->getVar('action') ? : $this->getVar('a');
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
        if (!$this->project || !$this->project->isActionAllowed($this->action)) {
            $this->tpl->assign('result', 'You are not allowed to perform this action.');
            return;
        }

        switch ($this->action) {
            case self::ACTION_MONITORING:
            case self::ACTION_BRANCH_HEAD:
                $this->loadBranchHead();
                break;

            case self::ACTION_TAG_HEAD:
                $this->loadTagHead();
                break;

            case self::ACTION_MERGE_BASE:
                $this->loadMergeBase();
                break;

            case self::ACTION_ONELINE_LOG:
                $this->loadOnelineLog();
                break;

            default:
                $this->tpl->assign('result', 'Cannot proceed your request');
        }
    }

    protected function loadBranchHead()
    {
        $branch = $this->getVar('branch') ? : 'master';
        $persist = $this->getVar('persist');

        $hash = 'Cant get commit hash for requested branch!';
        $response = ['status' => 'error'];
        if (!empty($this->project)) {
            $head = $this->project->GetCommit($branch);
            if (!empty($head)) {
                $hash = $head->GetHash();
                if ($persist) {
                    $res = \GitPHP\Db::getInstance()->saveBranchHead($this->project->GetProject(), $branch, $hash);
                    $response['status'] = ($res ? 'ok' : 'err');
                }
            }
        }
        if ($persist) {
            header('Content-Type: application/json; charset=UTF-8');

            echo json_encode($response);

//            \GitPHP\Log::GetInstance()->printHtmlHeader();
//            \GitPHP\Log::GetInstance()->printHtml();
//            \GitPHP\Log::GetInstance()->printHtmlFooter();

            die;
        }
        $this->tpl->assign('result', $hash);
    }

    protected function loadTagHead()
    {
        $tag_name = $this->getVar('tag');
        $hash = 'Cant find requested tag in project';

        if (!empty($this->project)) {
            if ($head = $this->project->GetCommit($tag_name)) {
                $hash = $head->GetHash();
            }
        }
        $this->tpl->assign('result', $hash);
    }

    protected function loadMergeBase()
    {
        $first_commit = $this->getVar('first');
        $second_commit = $this->getVar('second');

        $hash = "Can't find merge-base for requested commits";
        if (!empty($this->project) && $first_commit && $second_commit) {
            if ($Commit = $this->project->getMergeBase($first_commit, $second_commit)) {
                $hash = $Commit->GetHash();
            }
        }
        $this->tpl->assign('result', $hash);
    }

    protected function loadOnelineLog()
    {
        $first_commit  = $this->getVar('first');
        $second_commit = $this->getVar('second');

        if (!$first_commit) {
            $first_commit = $this->getVar('to');
        }

        if (!$second_commit) {
            $second_commit = $this->getVar('from');
        }

        $no_merges = $this->getVar('no-merges');

        $log = "Can't get short log for requested commits";
        if (!empty($this->project) && $first_commit && $second_commit) {
            $exe = new \GitPHP\Git\GitExe($this->project);

            $args = ['--oneline', $first_commit, "^{$second_commit}", '--'];
            if ($no_merges) {
                array_unshift($args, '--no-merges');
            }
            $log  = $exe->Execute(GIT_LOG, $args);
        }
        $this->tpl->assign('result', $log);
    }
}
