<?php
namespace GitPHP\Controller;

class Commitdiff extends DiffBase
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
            return 'commitdiffplain.tpl';
        }
        return 'commitdiff.tpl';
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
        $key = (isset($this->params['hash']) ? $this->params['hash'] : '')
            . '|' . (isset($this->params['hashparent']) ? $this->params['hashparent'] : '')
            . '|' . (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true) ? '1' : '');

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
            return __('commitdiff');
        }
        return 'commitdiff';
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

        if (isset($_GET['h'])) $this->params['hash'] = $_GET['h'];
        if (isset($_GET['hp'])) $this->params['hashparent'] = $_GET['hp'];
        $this->params['review'] = isset($_GET['review']) ? $_GET['review'] : '';
        $this->params['retbranch'] = isset($_GET['retbranch']) ? $_GET['retbranch'] : null;
        $this->params['context'] = isset($_COOKIE['diff_context']) ? (int)$_COOKIE['diff_context'] : true;
        if ($this->params['context'] < 1 || $this->params['context'] > 9999) {
            $this->params['context'] = 3;
        }
        $this->params['ignorewhitespace'] = isset($_COOKIE['ignore_whitespace']) ? $_COOKIE['ignore_whitespace'] == 'true' : false;
        $this->params['ignoreformat'] = isset($_COOKIE['ignore_format']) ? $_COOKIE['ignore_format'] == 'true' : false;
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
            $this->headers[] = 'Content-disposition: inline; filename="git-' . $this->params['hash'] . '.patch"';
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
        $co = $this->project->GetCommit($this->params['hash']);
        $this->tpl->assign('commit', $co);

        if (isset($this->params['hashparent'])) {
            $this->tpl->assign("hashparent", $this->params['hashparent']);
        }

        if (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true)) {
            $this->tpl->assign('extrascripts', array('commitdiff'));
        }

        $renames = true;
        $DiffContext = new \DiffContext();
        $DiffContext->setRenames($renames)
            ->setContext($this->params['context'])
            ->setIgnoreWhitespace($this->params['ignorewhitespace'])
            ->setIgnoreFormatting($this->params['ignoreformat']);
        $treediff = new \GitPHP_TreeDiff(
            $this->project,
            $this->params['hash'],
            (isset($this->params['hashparent']) ? $this->params['hashparent'] : ''),
            $DiffContext
        );

        $this->loadReviewsLinks($co, implode('', $co->GetComment()));

        if (empty($this->params['sidebyside'])) {
            include_once(\GitPHP_Util::AddSlash('lib/syntaxhighlighter') . "syntaxhighlighter.php");
            $this->tpl->assign('sexy', 1);
            $this->tpl->assign('highlighter_no_ruler', 1);
            $this->tpl->assign('highlighter_diff_enabled', 1);
            $brashes = [];

            $extensions = [];
            $statuses = [];
            $folders = [];
            foreach ($treediff as $filediff) {
                /** @var \GitPHP_FileDiff $filediff */

                $extensions[$filediff->getToFileExtension()] = $filediff->getToFileExtension();
                $statuses[$filediff->GetStatus()] = $filediff->GetStatus();
                $folders[$filediff->getToFileRootFolder()] = $filediff->getToFileRootFolder();

                $SH = new \SyntaxHighlighter($filediff->GetToFile());
                $brashes = array_merge($SH->getBrushesList(), $brashes);
                $filediff->SetDecorationData(
                    [
                        'highlighter_brushes' => $SH->getBrushesList(),
                        'highlighter_brush_name' => $SH->getBrushName(),
                    ]
                );
                $this->tpl->assign('extracss_files', $SH->getCssList());
                $this->tpl->assign('extrajs_files', $SH->getJsList());
            }
            $this->tpl->assign('highlighter_brushes', $brashes);
            $this->tpl->assign('extensions', $extensions);
            $this->tpl->assign('statuses', $statuses);
            $this->tpl->assign('folders', $this->filterRootFolders($folders));
        }
        $this->tpl->assign('treediff', $treediff);
        $this->tpl->assign('retbranch', $this->params['retbranch']);
        $this->tpl->assign('diffcontext', is_int($this->params['context']) ? $this->params['context'] : 3);
        $this->tpl->assign('ignorewhitespace', $this->params['ignorewhitespace']);
        $this->tpl->assign('ignoreformat', $this->params['ignoreformat']);
    }
}
