<?php

class GitPHP_Controller_Login extends GitPHP_ControllerBase
{
    protected function GetTemplate()
    {
        return 'login.tpl';
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
        return 'login';
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
        return 'login';
    }

    protected function ReadQuery()
    {
        $this->params['back'] = isset($_GET['back']) ? $_GET['back'] : '';
        $php_format_cookie = str_replace('.', '_', \GitPHP\Jira::getCookieName());
        $this->params['crowd_token_key'] = (isset($_COOKIE[$php_format_cookie])) ? $_COOKIE[$php_format_cookie] : false;
        $this->params['post'] = false;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->params['post'] = true;
            $this->params['remember'] = (isset($_POST['remember'])) ? $_POST['remember'] : 0;
        }
        $this->params['login'] = (isset($_POST['login'])) ? htmlspecialchars(trim($_POST['login'])) : false;
        $this->params['password'] = (isset($_POST['password'])) ? $_POST['password'] : false;
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
        $err = $auth_result = false;

        if ($this->params['post'] && !empty($this->params['login']) && !empty($this->params['password'])) {
            if (\GitPHP_Config::GetInstance()->GetAuthMethod() == \GitPHP_Config::AUTH_METHOD_CROWD) {
                list ($auth_result, $err) = \GitPHP\Jira::instance()->crowdAuthenticatePrincipal($this->params['login'], $this->params['password']);
            } else if (\GitPHP_Config::GetInstance()->GetAuthMethod() == \GitPHP_Config::AUTH_METHOD_JIRA) {
                list ($auth_result, $err) = \GitPHP\Jira::instance()->restAuthenticateByUsernameAndPassword($this->params['login'], $this->params['password']);
            } else if (\GitPHP_Config::GetInstance()->GetAuthMethod() == \GitPHP_Config::AUTH_METHOD_CONFIG) {
                if (\GitPHP_Config::AUTH_USER['name'] === $this->params['login'] && \GitPHP_Config::AUTH_USER['password'] === $this->params['password']) {
                    $auth_result = [
                        'user_id' => \GitPHP_Config::AUTH_USER['name'],
                        'user_name' => \GitPHP_Config::AUTH_USER['name'],
                        'user_email' => \GitPHP_Config::AUTH_USER['name'],
                        'user_token' => md5(\GitPHP_Config::AUTH_USER['name'].$this->params['password'].microtime())
                    ];
                } else {
                    $err = 'User or password does not exists.';
                }
            } else if (\GitPHP_Config::GetInstance()->GetAuthMethod() == \GitPHP_Config::AUTH_METHOD_REDMINE) {
                list ($auth_result, $err) = \GitPHP\Redmine::instance()->restAuthenticateByUsernameAndPassword($this->params['login'], $this->params['password']);
            } else {
                $err = 'Auth method is not defined. Please check config file.';
            }
        } else if ($this->params['crowd_token_key']) {
            if (\GitPHP_Config::GetInstance()->GetAuthMethod() == \GitPHP_Config::AUTH_METHOD_CROWD) {
                list ($auth_result, $err) = \GitPHP\Jira::instance()->crowdAuthenticatePrincipalByCookie($this->params['crowd_token_key']);
            } else if (\GitPHP_Config::GetInstance()->GetAuthMethod() == \GitPHP_Config::AUTH_METHOD_JIRA) {
                list ($auth_result, $err) = \GitPHP\Jira::instance()->restAuthenticateByCookie($this->params['crowd_token_key']);
            }
        }

        $User = null;
        if ($auth_result) {
            $User = GitPHP_User::fromAuthData($auth_result);
            if (\GitPHP_Config::CHECK_ACCESS_GROUP) {
                $Acl = new \GitPHP\Acl(\GitPHP\Jira::instance());
                if (!$Acl->isCodeAccessAllowed($User)) {
                    $User = null;
                    $err = 'You haven\'t permission to view code source!';
                }
            }
        }

        if ($User) {
            $this->Session->setUser($User);
            if (!empty($this->params['remember'])) {
                $expire = time() + 60 * 60 * 24 * 30 * 12;
                $domain = $_SERVER['HTTP_HOST'];
                if (in_array(\GitPHP_Config::GetInstance()->GetAuthMethod(), [\GitPHP_Config::AUTH_METHOD_CROWD, \GitPHP_Config::AUTH_METHOD_JIRA])) {
                    setcookie(\GitPHP\Jira::getCookieName(), $User->getToken(), $expire, '/', $domain, false, true);
                }
            }
            $this->redirect($this->params['back']);
        } else {
            $this->tpl->assign('cur_login', $this->params['login']);
            $this->tpl->assign('cur_password', $this->params['password']);
            $this->tpl->assign('auth_error', $err);
        }
    }
}
