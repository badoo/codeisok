<?php
namespace GitPHP\Controller;

class Branchdiff extends DiffBase
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->project) {
            throw new \GitPHP_MessageException(__('Project is required'), true);
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
        if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
            return 'branchdiffplain.tpl';
        }
        return 'branchdiff.tpl';
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
        $mode = '1';

        if (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true)) {
            $mode = '2';
        }

        $key = (isset($this->params['hash']) ? $this->params['hash'] : '')
            . '|' . (isset($this->params['hashparent']) ? $this->params['hashparent'] : '')
            . '|' . $mode
            . '|' . (isset($this->params['treediff']) ? 'treediff' : '');

        return $key;
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
            return __('branchdiff');
        }
        return 'branchdiff';
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
        parent::ReadQuery();

        $this->params['branch'] = isset($_GET['branch']) ? $_GET['branch'] : '';
        $this->params['review'] = $this->getReviewNumber();
        // it looks like a possibly wrong code
        $this->params['base'] = $this->Session->get($this->project->GetProject() . \GitPHP_Session::SESSION_BASE_BRANCH . $this->params['branch'], '');

        if (isset($_REQUEST['base'])) {
            $this->params['base'] = $_REQUEST['base'];
            if (empty($this->params['review'])) {
                $this->Session->set($this->project->GetProject() . \GitPHP_Session::SESSION_BASE_BRANCH . $this->params['branch'], $this->params['base']);
            }
        } else if (empty($this->params['base'])) {
            $force_base_per_repo = \GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::FORCE_BASE_BRANCH_PER_REPOSITORY, []);
            $force_base = $force_base_per_repo[$this->project->GetProject()] ?? false;
            if ($force_base) {
                $this->params['base'] = $force_base;
            } else {
                $base_branches = $this->project->GetBaseBranches($this->params['branch']);
                $top_branch = array_shift($base_branches);
                if ('master' != $top_branch) {
                    $this->params['base'] = $top_branch;
                }
            }
        }


        $this->params['ignorewhitespace'] = isset($_COOKIE['ignore_whitespace']) ? $_COOKIE['ignore_whitespace'] == 'true' : false;
        $this->params['ignoreformat'] = isset($_COOKIE['ignore_format']) ? $_COOKIE['ignore_format'] == 'true' : false;
        $this->params['show_hidden'] = isset($_GET['show_hidden']) ? (bool)$_GET['show_hidden'] : false;
    }

    private function getReviewNumber()
    {
        if(!isset($_GET['review'])){
            return 0;
        }
        $review_id = (int)$_GET['review'];
        if(!$review_id || empty(\GitPHP\Db::getInstance()->getReviewList([$review_id]))){
            return 0;
        }
        return $review_id;
    }

    /**
     * LoadHeaders
     *
     * Loads headers for this template
     *
     * @access protected
     */
    protected function LoadHeaders()
    {
        parent::LoadHeaders();

        if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
            $this->headers[] = 'Content-disposition: inline; filename="git-' . $this->params['branch'] . '.patch"';
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
        parent::LoadData();
        $co = $this->project->GetCommit($this->params['branch']);
        $toHash = null;
        if (!$co) {
            $co = \GitPHP\Db::getInstance()->getBranchHead($this->params['branch']);
            if ($co) $co = $this->project->GetCommit($co);
            if (!$co) return;
            $toHash = $co->GetHash();
        }

        $renames = true;
        $DiffContext = new \DiffContext();
        $DiffContext->setContext($this->params['context'])
            ->setIgnoreWhitespace($this->params['ignorewhitespace'])
            ->setIgnoreFormatting($this->params['ignoreformat'])
            ->setRenames($renames)
            ->setShowHidden($this->params['show_hidden']);

        if (in_array($this->project->GetCategory(), \GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::SKIP_SUPPRESS_FOR_CATEGORY, []))) {
            $DiffContext->setSkipSuppress(true);
        }
        $branchdiff = new \GitPHP_BranchDiff($this->project, $this->params['branch'], $this->params['base'], $DiffContext);
        if ($toHash) $branchdiff->SetToHash($toHash);
        if (preg_match('/[0-9a-f]{40}/i', $this->params['base'])) {
            $branchdiff->setFromHash($this->params['base']);
        }
        $branchdiff->rewind();

        $this->tpl->assign('branch', $this->params['branch']);
        if (!preg_match('#^[0-9a-z]{40}$#i', $this->params['branch'])) {
            $this->tpl->assign('branch_name', $this->params['branch']);
        }
        $this->tpl->assign('commit', $co);

//    	if (isset($this->params['hashparent'])) {
//    		$this->tpl->assign("hashparent", $this->params['hashparent']);
//    	}

        if (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true)) {
            $this->tpl->assign('extrascripts', array('commitdiff'));
        }

        if (empty($this->params['sidebyside'])) {
            include_once(\GitPHP_Util::AddSlash('lib/syntaxhighlighter') . "syntaxhighlighter.php");
            $this->tpl->assign('sexy', 1);
            $this->tpl->assign('highlighter_no_ruler', 1);
            $this->tpl->assign('highlighter_diff_enabled', 1);
            $brashes = [];

            $extensions = [];
            $statuses = [];
            $folders = [];
            foreach ($branchdiff as $filediff) {
                $SH = new \SyntaxHighlighter($filediff->getToFile());
                $brashes = array_merge($SH->getBrushesList(), $brashes);

                $extensions[$filediff->getToFileExtension()] = $filediff->getToFileExtension();
                $statuses[$filediff->GetStatus()] = $filediff->GetStatus();
                $folders[$filediff->getToFileRootFolder()] = $filediff->getToFileRootFolder();

                $filediff->SetDecorationData(
                    [
                        'highlighter_brushes' => $SH->getBrushesList(),
                        'highlighter_brush_name' => $SH->getBrushName(),
                    ]
                );
                $this->tpl->assign('extracss_files', $SH->getCssList());
                $this->tpl->assign('extrajs_files', $SH->getJsList());
            }
            $this->tpl->assign('folders', $this->filterRootFolders($folders));
            $this->tpl->assign('statuses', $statuses);
            $this->tpl->assign('extensions', $extensions);
            $this->tpl->assign('highlighter_brushes', $brashes);
        }

        $this->tpl->assign('branchdiff', $branchdiff);
        $this->tpl->assign('enablesearch', false);
        $this->tpl->assign('enablebase', true);
        $this->tpl->assign('base', $this->params['base']);
        $base_branches = $this->project->GetBaseBranches($this->params['branch']);
        if ($this->params['base'] && !in_array($this->params['base'], $base_branches)) array_unshift($base_branches, $this->params['base']);
        $this->tpl->assign('base_branches', $base_branches);

        $this->loadReviewsLinks($co, $this->params['branch']);

        $this->tpl->assign('branch', $this->params['branch']);
        $this->tpl->assign('base_disabled', !empty($this->params['review']));
        $this->tpl->assign('diffcontext', is_int($this->params['context']) ? $this->params['context'] : 3);
        $this->tpl->assign('ignorewhitespace', $this->params['ignorewhitespace']);
        $this->tpl->assign('ignoreformat', $this->params['ignoreformat']);
    }
}
