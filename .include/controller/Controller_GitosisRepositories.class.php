<?php

class GitPHP_Controller_GitosisRepositories extends GitPHP_Controller_GitosisBase
{
    protected $_displays = array('Yes', 'No');
    protected $_diffs_by_email = array(Model_Gitosis::DIFF_TYPE_CUMULATIVE, Model_Gitosis::DIFF_TYPE_COMMIT);
    protected $_filter_commits = array('No', 'Yes');
    protected $_is_it_lib = array('No', 'Yes');

    protected $_edit_project;

    protected function ReadQuery()
    {
        $this->Session->getUser()->isGitosisAdmin();
        if (isset($_GET['id']) && is_string($_GET['id'])) {
            $this->_edit_project = $this->ModelGitosis->getRepository((int)$_GET['id']);
        }

        if (count($_POST)) {
            $project = empty($_POST['project']) || !is_string($_POST['project']) ? '' : $_POST['project'];
            $project = trim($project);

            $description = empty($_POST['description']) || !is_string($_POST['description']) ? '' : $_POST['description'];
            $description = trim($description);

            $category = empty($_POST['category']) || !is_string($_POST['category']) ? '' : $_POST['category'];
            $category = trim($category);

            $notify_email = empty($_POST['notify_email']) || !is_string($_POST['notify_email'])
                ? '' : $_POST['notify_email'];
            $notify_email = trim($notify_email);

            $display = empty($_POST['display']) || !in_array($_POST['display'], $this->_displays)
                ? '' : $_POST['display'];
            $display = trim($display);

            $diffs_by_email = empty($_POST['diffs_by_email']) || !in_array($_POST['diffs_by_email'], $this->_diffs_by_email)
                ? '' : $_POST['diffs_by_email'];
            $diffs_by_email = trim($diffs_by_email);

            $filter_commits = empty($_POST['filter_commits']) || !in_array($_POST['filter_commits'], $this->_filter_commits)
                ? '' : $_POST['filter_commits'];
            $filter_commits = trim($filter_commits);

            $is_it_lib = empty($_POST['is_it_lib']) || !in_array($_POST['is_it_lib'], $this->_is_it_lib)
                ? '' : $_POST['is_it_lib'];
            $is_it_lib = trim($is_it_lib);

            if (!$project) {
                $this->_form_errors[] = 'Project can not be empty.';
            } else if (!preg_match('/\.git$/', $project)) {
                $this->_form_errors[] = 'Project name must end with ".git".';
            }

            if (empty($this->_form_errors)) {
                $this->ModelGitosis->saveRepository($project, $description, $category, $notify_email, $display, $diffs_by_email, $filter_commits, $is_it_lib);
                $this->redirect('/?a=gitosis&section=repositories');
            }

            $this->_edit_project = $_POST;
        }
    }

    protected function LoadData()
    {
        parent::LoadData();

        $this->tpl->assign('projects', $this->ModelGitosis->getRepositories());

        $this->tpl->assign('displays', $this->_displays);

        $this->tpl->assign('edit_project', $this->_edit_project);

        $this->tpl->assign('diffs_by_email', $this->_diffs_by_email);

        $this->tpl->assign('filter_commits', $this->_filter_commits);

        $this->tpl->assign('is_it_lib', $this->_is_it_lib);
    }
}
