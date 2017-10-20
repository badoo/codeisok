<?php

namespace GitPHP\Controller;

class GitosisRepositories extends GitosisBase
{
    protected $displays = array('Yes', 'No');
    protected $restricted = ['No', 'Yes'];

    protected $edit_project;

    protected function ReadQuery()
    {
        $this->Session->getUser()->isGitosisAdmin();
        if (isset($_GET['id']) && is_string($_GET['id'])) {
            $this->edit_project = $this->ModelGitosis->getRepository((int)$_GET['id']);
        }

        if (count($_POST)) {
            $project = empty($_POST['project']) || !is_string($_POST['project']) ? '' : $_POST['project'];
            $project = trim($project);

            $description = empty($_POST['description']) || !is_string($_POST['description']) ? '' : $_POST['description'];
            $description = trim($description);

            $category = empty($_POST['category']) || !is_string($_POST['category']) ? '' : $_POST['category'];
            $category = trim($category);

            $notify_email = empty($_POST['notify_email']) || !is_string($_POST['notify_email']) ? '' : $_POST['notify_email'];
            $notify_email = trim($notify_email);

            $display = empty($_POST['display']) || !in_array($_POST['display'], $this->displays) ? '' : $_POST['display'];
            $display = trim($display);

            $restricted = empty($_POST['restricted']) || !in_array($_POST['restricted'], $this->restricted) ? '' : $_POST['restricted'];
            $restricted = trim($restricted);

            $current_user = $this->Session->getUser()->getEmail() ?? $this->Session->getUser()->getName();
            $owner = empty($_POST['owner']) || !is_string($_POST['owner']) ? $current_user : $_POST['owner'];
            $owner = trim($owner);

            if (!$project) {
                $this->_form_errors[] = 'Project can not be empty.';
            } else if (!preg_match('/\.git$/', $project)) {
                $this->_form_errors[] = 'Project name must end with ".git".';
            }

            if (empty($this->_form_errors)) {
                $this->ModelGitosis->saveRepository(
                    $project,
                    $description,
                    $category,
                    $notify_email,
                    $restricted,
                    $display,
                    $owner
                );
                //creating the repo
                $base_path = \GitPHP_Config::GetInstance()->GetValue(\GitPHP_Config::PROJECT_ROOT);
                if (\GitPHP_Config::GetInstance()->GetValue(\GitPHP_Config::UPDATE_AUTH_KEYS_FROM_WEB)) {
                    exec("cd " . $base_path . ";git init --bare " . escapeshellarg($project), $out, $retval);
                    if ($retval) {
                        $this->_form_errors[] = 'Can\'t init bare repo in ' . $base_path;
                    }
                }
                $this->redirect('/?a=gitosis&section=repositories');
            }

            $this->edit_project = $_POST;
        }
    }

    protected function LoadData()
    {
        parent::LoadData();

        $this->tpl->assign('projects', $this->ModelGitosis->getRepositories());
        $this->tpl->assign('displays', $this->displays);
        $this->tpl->assign('restricted', $this->restricted);
        $this->tpl->assign('edit_project', $this->edit_project);
    }

    protected function getCurrentSection()
    {
        return 'repositories';
    }
}
