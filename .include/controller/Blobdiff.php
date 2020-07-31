<?php
namespace GitPHP\Controller;

class Blobdiff extends DiffBase
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
        if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
            return 'blobdiffplain.tpl';
        }
        return 'blobdiff.tpl';
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
        return (isset($this->params['hashbase']) ? $this->params['hashbase'] : '') . '|' . (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['hashparent']) ? $this->params['hashparent'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '') . '|' . (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true) ? '1' : '');
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
            return __('blobdiff');
        }
        return 'blobdiff';
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

        if (isset($_GET['f'])) $this->params['file'] = $_GET['f'];
        if (isset($_GET['h'])) $this->params['hash'] = $_GET['h'];
        if (isset($_GET['hb'])) $this->params['hashbase'] = $_GET['hb'];
        if (isset($_GET['hp'])) $this->params['hashparent'] = $_GET['hp'];
        $this->params['escape'] = isset($_GET['escape']);
        $this->params['context'] = isset($_COOKIE['diff_context']) ? (int)$_COOKIE['diff_context'] : true;
        if ($this->params['context'] < 1 || $this->params['context'] > 999) {
            $this->params['context'] = 3;
        }
        $this->params['ignorewhitespace'] = isset($_COOKIE['ignore_whitespace']) ? $_COOKIE['ignore_whitespace'] == 'true' : false;
        $this->params['ignoreformat'] = isset($_COOKIE['ignore_format']) ? $_COOKIE['ignore_format'] == 'true' : false;
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
        if (isset($this->params['file'])) $this->tpl->assign('file', $this->params['file']);

        $DiffContext = new \DiffContext();
        $DiffContext->setContext($this->params['context'])
            ->setIgnoreWhitespace($this->params['ignorewhitespace'])
            ->setIgnoreFormatting($this->params['ignoreformat']);
        $filediff = new \GitPHP\Git\FileDiff(
            $this->project,
            $this->params['hashparent'],
            $this->params['hash'],
            $DiffContext
        );
        $this->tpl->assign('filediff', $filediff);

        $this->tpl->assign('escape', $this->params['escape']);

        if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
            return;
        }

        $commit = $this->project->GetCommit($this->params['hashbase']);
        $this->tpl->assign('commit', $commit);

        $blobparent = $this->project->GetBlob($this->params['hashparent']);
        $blobparent->SetCommit($commit);
        $blobparent->SetPath($this->params['file']);
        $this->tpl->assign('blobparent', $blobparent);

        $blob = $this->project->GetBlob($this->params['hash']);
        $blob->SetPath($this->params['file']);
        $this->tpl->assign('blob', $blob);

        $tree = $commit->GetTree();
        $this->tpl->assign('tree', $tree);
    }
}
