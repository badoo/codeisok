<?php
namespace GitPHP\Controller;

class ProjectList extends Base
{
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
        if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
            return 'opml.tpl';
        } else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
            return 'projectindex.tpl';
        }
        return 'projectlist.tpl';
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
        if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
            return '';
        } else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
            return '';
        }
        return $this->params['order'] . '|' . (isset($this->params['search']) ? $this->params['search'] : '');
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
        if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
            if ($local) {
                return __('opml');
            }
            return 'opml';
        } else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
            if ($local) {
                return __('project index');
            }
            return 'project index';
        }
        if ($local) {
            return __('projects');
        }
        return 'projects';
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
        if (isset($_GET['o'])) $this->params['order'] = $_GET['o'];
        else $this->params['order'] = 'project';
        if (isset($_GET['s'])) $this->params['search'] = $_GET['s'];

        $this->params['text'] = '';
        $this->params['projects'] = array();
        if (isset($_POST['t']) && isset($_POST['projects']) && count($_POST['projects'])) {
            $this->params['text'] = $_POST['t'];
            $this->params['projects'] = $_POST['projects'];
        }
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
        if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
            $this->headers[] = "Content-type: text/xml; charset=UTF-8";
            \GitPHP\Log::GetInstance()->SetEnabled(false);
        } else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
            $this->headers[] = "Content-type: text/plain; charset=utf-8";
            $this->headers[] = "Content-Disposition: inline; filename=\"index.aux\"";
            \GitPHP\Log::GetInstance()->SetEnabled(false);
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
        $this->tpl->assign('order', $this->params['order']);
        $this->tpl->assign('allow_create_projects', \GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::ALLOW_USER_CREATE_REPOS, false));

        $projectList = \GitPHP_ProjectList::GetInstance();
        $projectList->Sort($this->params['order']);
        $this->tpl->assign('projectlist', $projectList);

        $this->tpl->assign('text', htmlspecialchars($this->params['text']));
        $this->tpl->assign('projects', $this->params['projects']);
        $this->tpl->assign('searchmode', 0);
        if (count($this->params['projects'])) {
            $this->tpl->assign('searchmode', 1);
        }

        if ((empty($this->params['opml']) || ($this->params['opml'] !== true))
            && (empty($this->params['txt']) || ($this->params['txt'] !== true))
            && (!empty($this->params['search']))) {
            $this->tpl->assign('search', $this->params['search']);
            $matches = $projectList->Filter($this->params['search']);
            if (count($matches) > 0) {
                $this->tpl->assign('projectlist', $matches);
            }
        }

        if ((empty($this->params['opml']) || ($this->params['opml'] !== true))
            && (empty($this->params['txt']) || ($this->params['txt'] !== true))) {
            $this->tpl->assign('extrascripts', array('projectlist'));
        }
    }
}
