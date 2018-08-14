<?php

namespace GitPHP\Controller;

class GitosisUsers extends GitosisBase
{
    const ACCESS_MODE_NORMAL       = 'normal';
    const ACCESS_MODE_ALLOW_ALL    = 'everywhere';
    const ACCESS_MODE_ALLOW_ALL_RO = 'everywhere-ro';

    protected $edit_user;
    protected $access_modes = [self::ACCESS_MODE_NORMAL, self::ACCESS_MODE_ALLOW_ALL, self::ACCESS_MODE_ALLOW_ALL_RO];

    protected function ReadQuery()
    {
        if (isset($_GET['id']) && is_string($_GET['id'])) {
            if (isset($_GET['delete'])) {
                $this->ModelGitosis->removeUser((int)$_GET['id']);
            } else {
                $this->edit_user = $this->ModelGitosis->getUser((int)$_GET['id']);
            }
        }

        if (count($_POST)) {
            $username = empty($_POST['username']) || !is_string($_POST['username']) ? '' : $_POST['username'];
            $username = trim($username);

            $email = empty($_POST['email']) || !is_string($_POST['email']) ? '' : $_POST['email'];
            $email = trim($email);

            $public_key = empty($_POST['public_key']) || !is_string($_POST['public_key']) ? '' : $_POST['public_key'];
            $public_key = trim($public_key);

            $access_mode = empty($_POST['access_mode']) || !in_array($_POST['access_mode'], $this->access_modes) ? '' : $_POST['access_mode'];
            $access_mode = trim($access_mode);

            $comment = empty($_POST['comment']) || !is_string($_POST['comment']) ? '' : $_POST['comment'];
            $comment = trim($comment);

            if (!$username) {
                $this->_form_errors[] = 'Username can not be empty.';
            }
            if (!$public_key) {
                $this->_form_errors[] = 'Public key can not be empty.';
            }

            if ($username && $public_key) {
                $this->ModelGitosis->saveUser($username, $email, $public_key, $access_mode, $comment);
                if (\GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::UPDATE_AUTH_KEYS_FROM_WEB, false)) {
                    if (!\GitPHP_Gitosis::addKey($username, $public_key)) {
                        $this->_form_errors[] = "Can't write key file!";
                    } else {
                        $this->redirect('/?a=gitosis&section=users');
                    }
                }
            }

            $this->edit_user = $_POST;
        }
    }

    protected function LoadData()
    {
        parent::LoadData();

        $this->tpl->assign('users', $this->ModelGitosis->getUsers());
        $this->tpl->assign('access_modes', $this->access_modes);
        $this->tpl->assign('edit_user', $this->edit_user);
    }

    protected function getCurrentSection()
    {
        return 'users';
    }
}
