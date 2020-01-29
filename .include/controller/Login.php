<?php
namespace GitPHP\Controller;

class Login extends Base
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
        $this->params['back'] = !empty($_GET['back']) ? $_GET['back'] : '/';
        $php_format_cookie = str_replace('.', '_', \GitPHP\Jira::getCookieName());
        $this->params['crowd_token_key'] = $_COOKIE[$php_format_cookie] ?? false;
        $this->params['post'] = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->params['post'] = true;
            $this->params['remember'] = $_POST['remember'] ?? 0;
        }
        $this->params['login'] = (isset($_POST['login'])) ? htmlspecialchars(trim($_POST['login'])) : false;
        $this->params['password'] = $_POST['password'] ?? false;
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
            if (\GitPHP\Config::GetInstance()->GetAuthMethod() === \GitPHP\Config::AUTH_METHOD_CROWD) {
                list ($auth_result, $err) = \GitPHP\Jira::instance()->crowdAuthenticatePrincipal(
                    $this->params['login'],
                    $this->params['password']
                );
            } else if (\GitPHP\Config::GetInstance()->GetAuthMethod() === \GitPHP\Config::AUTH_METHOD_JIRA) {
                list ($auth_result, $err) = \GitPHP\Jira::instance()->restAuthenticateByUsernameAndPassword(
                    $this->params['login'],
                    $this->params['password']
                );
            } else if (\GitPHP\Config::GetInstance()->GetAuthMethod() === \GitPHP\Config::AUTH_METHOD_CONFIG) {
                $auth_user = \GitPHP\Config::GetInstance()->GetAuthUserByName($this->params['login']);
                if ($auth_user['name'] === $this->params['login'] && $auth_user['password'] === $this->params['password']) {
                    $auth_result = [
                        'user_id'    => $auth_user['name'],
                        'user_name'  => $auth_user['name'],
                        'user_email' => $auth_user['name'],
                        'user_token' => md5($auth_user['name'] . $auth_user['password'] . microtime()),
                    ];
                } else {
                    $err = 'Username or password does not exist.';
                }
            } else if (\GitPHP\Config::GetInstance()->GetAuthMethod() === \GitPHP\Config::AUTH_METHOD_REDMINE) {
                list ($auth_result, $err) = \GitPHP\Redmine::instance()->restAuthenticateByUsernameAndPassword(
                    $this->params['login'],
                    $this->params['password']
                );
            } else {
                $err = 'Auth method is not defined. Please check config file.';
            }
        } else if ($this->params['crowd_token_key']) {
            if (\GitPHP\Config::GetInstance()->GetAuthMethod() === \GitPHP\Config::AUTH_METHOD_CROWD) {
                list ($auth_result, $err) = \GitPHP\Jira::instance()->crowdAuthenticatePrincipalByCookie(
                    $this->params['crowd_token_key']
                );
            } else if (\GitPHP\Config::GetInstance()->GetAuthMethod() === \GitPHP\Config::AUTH_METHOD_JIRA) {
                list ($auth_result, $err) = \GitPHP\Jira::instance()->restAuthenticateByCookie(
                    $this->params['crowd_token_key']
                );
            }
        }

        $User = null;
        if ($auth_result) {
            $User = \GitPHP_User::fromAuthData($auth_result);
            if (\GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::CHECK_ACCESS_GROUP)) {
                $Acl = new \GitPHP\Acl(\GitPHP\Jira::instance(), \GitPHP\Redmine::instance());
                if (!$Acl->isCodeAccessAllowed($User)) {
                    $User = null;
                    $err = 'You don\'t have the permission to view source.';
                }
            }
        }

        if ($User) {
            $this->Session->setUser($User);
            if (!empty($this->params['remember'])) {
                $expire = time() + 60 * 60 * 24 * 30 * 12;
                $domain = $auth_result['cookie_domain'] ?? $_SERVER['HTTP_HOST'];
                $atlassian_auth_methods = [
                    \GitPHP\Config::AUTH_METHOD_CROWD,
                    \GitPHP\Config::AUTH_METHOD_JIRA,
                ];
                if (in_array(\GitPHP\Config::GetInstance()->GetAuthMethod(), $atlassian_auth_methods)) {
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
