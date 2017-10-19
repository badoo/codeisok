<?php
namespace GitPHP\Controller;

class GitosisAccess extends GitosisBase
{
    protected $scope = 'user';

    protected function ReadQuery()
    {
        if (isset($_POST['mode']) && is_string($_POST['mode']) && in_array($_POST['mode'], array('', 'writable', 'readonly'))) {
            $mode = $_POST['mode'];
            if (!empty($_POST['user_id']) && is_array($_POST['projects_ids']) && count($_POST['projects_ids'])) {
                $user_id = (int)$_POST['user_id'];
                $projects_ids = array_map('intval', $_POST['projects_ids']);
                switch ($mode) {
                    case '':
                        $this->ModelGitosis->delUserAccess($user_id, $projects_ids);
                        break;

                    case 'writable':
                    case 'readonly':
                        foreach ($projects_ids as $project_id) {
                            $this->ModelGitosis->saveUserAccess($user_id, $project_id, $mode);
                        }
                        break;
                }
            } elseif (!empty($_POST['project_id']) && is_array($_POST['user_ids']) && count($_POST['user_ids'])) {
                $project_id = (int)$_POST['project_id'];
                $user_ids = array_map('intval', $_POST['user_ids']);
                foreach ($user_ids as $user_id) {
                    switch ($mode) {
                        case '':
                            $this->ModelGitosis->delUserAccess($user_id, array($project_id));
                            break;

                        case 'writable':
                        case 'readonly':
                            $this->ModelGitosis->saveUserAccess($user_id, $project_id, $mode);
                            break;
                    }
                }
            }
        }
    }

    protected function LoadData()
    {
        parent::LoadData();
        if (!empty($_GET['scope']) && in_array($_GET['scope'], array('user', 'repo'))) {
            $this->scope = $_GET['scope'];
        }
        $this->tpl->assign('scope', $this->scope);
        if ('user' == $this->scope) {
            $user_id = 0;
            if (!empty($_GET['user_id'])) {
                $user_id = (int)$_GET['user_id'];
                $users = $this->ModelGitosis->getUser($user_id);
                $users = array($users['username'] => $users);
            } else {
                $users = $this->ModelGitosis->getUsers();
            }

            $this->tpl->assign('users', $users);

            $projects = $this->ModelGitosis->getRepositories();
            $this->tpl->assign('projects', $projects);

            $access = $this->ModelGitosis->getAccessGroupByUserId($user_id);
            $this->tpl->assign('access', $access);
        } else {
            $project_id = 0;
            if (!empty($_GET['project_id'])) {
                $project_id = (int)$_GET['project_id'];
                $projects = $this->ModelGitosis->getRepository($project_id);
                $projects = array($projects['project'] => $projects);
            } else {
                $projects = $this->ModelGitosis->getRepositories();
            }
            $this->tpl->assign('projects', $projects);
            $users = $this->ModelGitosis->getUsers();
            $this->tpl->assign('users', $users);
            $access = $this->ModelGitosis->getAccessGroupByRepositoryId($project_id);
            $this->tpl->assign('access', $access);
        }
    }

    protected function getCurrentSection()
    {
        return 'access';
    }
}
