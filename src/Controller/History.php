<?php
namespace GitPHP\Controller;

class History extends Base
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
        return 'history.tpl';
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
        return (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '');
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
            return __('history');
        }
        return 'history';
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
        if (isset($_GET['f'])) $this->params['file'] = $_GET['f'];
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        } else {
            $this->params['hash'] = 'HEAD';
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
        $this->tpl->assign('commit', $co);
        $this->tpl->assign('tree', $co->GetTree());

        $blobhash = $co->PathToHash($this->params['file']);
        $blob = $this->project->GetBlob($blobhash);
        $blob->SetCommit($co);
        $blob->SetPath($this->params['file']);
        $this->tpl->assign('blob', $blob);
    }
}
