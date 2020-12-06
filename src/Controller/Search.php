<?php
namespace GitPHP\Controller;

/**
 * Search controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class Search extends Base
{
    /**
     * Constants for the various search types
     */
    const GITPHP_SEARCH_COMMIT = 'commit';
    const GITPHP_SEARCH_AUTHOR = 'author';
    const GITPHP_SEARCH_COMMITTER = 'committer';
    const GITPHP_SEARCH_FILE = 'file';

    public function __construct()
    {
        if (!\GitPHP\Config::GetInstance()->GetValue('search', true)) {
            throw new \GitPHP\MessageException(__('Search has been disabled'), true);
        }

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
        if ($this->params['searchtype'] == self::GITPHP_SEARCH_FILE) {
            return 'searchfiles.tpl';
        }
        return 'search.tpl';
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
        return (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['searchtype']) ? sha1($this->params['searchtype']) : '') . '|' . (isset($this->params['search']) ? sha1($this->params['search']) : '') . '|' . (isset($this->params['page']) ? $this->params['page'] : 0);
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
            return __('search');
        }
        return 'search';
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
        if (!isset($this->params['searchtype'])) $this->params['searchtype'] = self::GITPHP_SEARCH_COMMIT;

        if ($this->params['searchtype'] == self::GITPHP_SEARCH_FILE) {
            if (!\GitPHP\Config::GetInstance()->GetValue('filesearch', true)) {
                throw new \GitPHP\MessageException(__('File search has been disabled'), true);
            }
        }

        if ((!isset($this->params['search'])) || (strlen($this->params['search']) < 2)) {
            throw new \GitPHP\MessageException(
                sprintf(
                    __n('You must enter search text of at least %1$d character', 'You must enter search text of at least %1$d characters', 2),
                    2
                ),
                true
            );
        }

        if (isset($_GET['h'])) $this->params['hash'] = $_GET['h'];
        else $this->params['hash'] = 'HEAD';
        if (isset($_GET['pg'])) $this->params['page'] = $_GET['pg'];
        else $this->params['page'] = 0;
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

        $results = array();
        if ($co) {
            switch ($this->params['searchtype']) {
                case self::GITPHP_SEARCH_COMMIT:
                    $results = $this->project->SearchCommit($this->params['search'], $co->GetHash(), 101, ($this->params['page'] * 100));
                    break;

                case self::GITPHP_SEARCH_AUTHOR:
                    $results = $this->project->SearchAuthor($this->params['search'], $co->GetHash(), 101, ($this->params['page'] * 100));
                    break;

                case self::GITPHP_SEARCH_COMMITTER:
                    $results = $this->project->SearchCommitter($this->params['search'], $co->GetHash(), 101, ($this->params['page'] * 100));
                    break;

                case self::GITPHP_SEARCH_FILE:
                    $results = $co->SearchFiles($this->params['search'], 101, ($this->params['page'] * 100));
                    break;

                default:
                    throw new \GitPHP\MessageException(__('Invalid search type'));
            }
        }

        if (count($results) < 1) {
            throw new \GitPHP\MessageException(sprintf(__('No matches for "%1$s"'), $this->params['search']), false);
        }

        if (count($results) > 100) {
            $this->tpl->assign('hasmore', true);
            $results = array_slice($results, 0, 100, true);
        } else {
            $this->tpl->assign('hasmore', false);
        }
        $this->tpl->assign('results', $results);

        $this->tpl->assign('tree', $co->GetTree());

        $this->tpl->assign('page', $this->params['page']);
    }
}
