<?php

class GitPHP_Controller_Logout extends GitPHP_ControllerBase
{
    protected function GetTemplate()
    {
        return 'logout.tpl';
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
        return 'logout';
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
        return 'logout';
    }

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
        $php_format_cookie = str_replace('.', '_', \GitPHP\Jira::getCookieName());
        foreach (array(\GitPHP\Jira::getCookieName(), $php_format_cookie) as $cookie) {
            $domain = $_SERVER['HTTP_HOST'];
            setcookie($cookie, '', 0, '/', $domain, false, true);
        }
        if ($this->Session->isAuthorized()) {
            $this->Session->logout();
            $this->redirect('/');
        }
    }
}
