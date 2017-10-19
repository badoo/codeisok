<?php
namespace GitPHP\Controller;

class ProjectCreate extends Base
{
    protected $form_errors = [];
    protected $displays = ['Yes', 'No'];
    protected $edit_project = ['project' => '', 'description' => '', 'category' => '', 'notify_email' => '', 'display' => 'Yes'];

    protected $ModelGitosis;

    protected function getModel()
    {
        if (!isset($this->ModelGitosis)) {
            $this->ModelGitosis = new \Model_Gitosis();
        }
        return $this->ModelGitosis;
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
        return 'projectcreate.tpl';
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
        return null;
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
        return 'Create project';
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

            $this->edit_project = [
                'project' => $project,
                'description' => $description,
                'category' => $category,
                'notify_email' => $notify_email,
                'display' => $display,
            ];

            if (!$project) {
                $this->form_errors[] = 'Project can not be empty.';
            } else if (!preg_match('/\.git$/', $project)) {
                $this->form_errors[] = 'Project name must end with ".git".';
            }

            if (empty($this->form_errors)) {
                $this->getModel()->saveRepository(
                    $project,
                    $description,
                    $category,
                    $notify_email,
                    $display,
                    $this->Session->getUser()->getEmail() ?? $this->Session->getUser()->getName()
                );
                //creating the repo
                if (\GitPHP_Config::GetInstance()->GetValue(\GitPHP_Config::UPDATE_AUTH_KEYS_FROM_WEB)) {
                    $base_path = \GitPHP_Config::GetInstance()->GetValue(\GitPHP_Config::PROJECT_ROOT);
                    exec("cd " . $base_path . ";git init --bare " . escapeshellarg($project), $out, $retval);
                    if ($retval) {
                        $this->form_errors[] = 'Can\'t init bare repo in ' . $base_path;
                    }
                }
                $this->redirect('/');
            }
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
        $this->tpl->assign('form_errors', $this->form_errors);
        $this->tpl->assign('displays', $this->displays);
        $this->tpl->assign('edit_project', $this->edit_project);
    }
}
