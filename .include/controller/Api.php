<?php
/**
 * @team QA <qa@corp.badoo.com>
 * @maintainer Aleksandr Izmaylov <a.izmaylov@corp.badoo.com>
 */

namespace GitPHP\Controller;

class Api implements ControllerInterface
{
    protected $project;

    public function Render()
    {
        $this->renderCommonHeaders();

        $Session = \GitPHP\Session::instance();
        if (!$Session->isAuthorized()) {
            $this->sendResponse(['error' => "Unauthorized api usage is forbidden"], 403);
            return;
        }

        if (!$this->getProject()) {
            $action = $this->getActionWithoutProject();
            switch ($action) {
                case "project":
                    $this->handleProjectRequest();
                    break;

                default:
                    $this->renderNotFound();
            }
            return;
        }
        $action = $this->getAction();

        switch ($action) {
            case "commit":
                $this->handleCommitRequest();
                break;

            case "log":
                $this->handleLogRequest();
                break;

            case "branch-log":
                $this->handleBranchLogRequest();
                break;

            case "diff-tree":
                $this->handleDiffTreeRequest();
                break;

            case "merge-base":
                $this->handleMergeBaseRequest();
                break;

            case "contributors":
                $this->handleContributorsRequest();
                break;

            default:
                $this->renderNotFound();
        }
    }

    protected function renderCommonHeaders()
    {
        header('Content-Type: application/json');
    }

    protected function getAction()
    {
        // uri should be in form /api/project.git/action
        return explode(".git/", $_SERVER['DOCUMENT_URI'], 2)[1] ?? "";
    }

    protected function getActionWithoutProject()
    {
        // instead of $this->getAction() method this time uri will be in form /api/action
        return explode("api/", $_SERVER['DOCUMENT_URI'], 2)[1] ?? "";
    }

    protected function getProject()
    {
        if (!isset($this->project)) {
            // /api/dir/project.git/action -> dir/project.git
            $last_uri_part = explode("/", $_SERVER['DOCUMENT_URI'], 3)[2] ?? "";
            $dot_git_pos = strpos($last_uri_part, ".git");
            if ($dot_git_pos === false) {
                $this->project = false;
            } else {
                try {
                    $this->project = \GitPHP\Git\ProjectList::GetInstance()->GetProject(
                        substr($last_uri_part, 0, $dot_git_pos + 4)
                    );
                } catch (\Exception $e) {
                    $this->project = false;
                }
            }
        }
        return $this->project;
    }

    protected function handleCommitRequest()
    {
        $search_for = $_REQUEST["hash"] ?? '';
        if (!$search_for) {
            $this->renderNotFound();
            return;
        }

        $hash = $this->getProject()->GetObjectHash($search_for, "commit");
        if (!$hash) {
            $this->renderNotFound("Cannot find commit by hash {$search_for}");
            return;
        }

        $Commit = $this->getProject()->GetCommit($hash);
        $this->sendResponse($this->renderCommit($Commit));
    }

    protected function handleLogRequest()
    {
        $range_to = $_REQUEST['range-to'] ?? null;
        if ($range_to) { // at least one of them is set
            $range_from = $_REQUEST['range-from'] ?? null;
            $rev_list_opts = $_REQUEST['opts'] ?? [];
            $limit = $_REQUEST['limit'] ?? $this->getDefaultLogLimit();

            if (!is_array($rev_list_opts)) {
                $rev_list_opts = [$rev_list_opts];
            }

            $this->sendResponse(
                [
                    'commits' => array_map(
                        function (\GitPHP\Git\Commit $Commit) { return $this->renderCommit($Commit); },
                        $this->getProject()->GetLog($range_to, $limit, 0, $range_from, $rev_list_opts)
                    )
                ]
            );

            return;
        }

        $this->renderNotFound("Nothing to get log for");
    }

    protected function getDefaultLogLimit()
    {
        if (!empty($_REQUEST['include'])) {
            return 100;
        }
        return 1000;
    }

    protected function handleBranchLogRequest()
    {
        $branch = $_REQUEST['name'] ?? false;
        if (empty($branch)) {
            $this->renderNotFound("No branch name provided");
        }

        $compare_with = $_REQUEST['compare-with'] ?? 'master';

        $rev_list_options = [];
        if ($_REQUEST['no-merges'] ?? false) {
            $rev_list_options[] = '--no-merges';
        }

        $this->sendResponse(
            [
                'commits' => array_map(
                    function (\GitPHP\Git\Commit $Commit) { return $this->renderCommit($Commit); },
                    $this->getProject()->GetLog($branch, 1000, 0, $compare_with, $rev_list_options)
                )
            ]
        );
    }

    protected function handleDiffTreeRequest()
    {
        $tree = $_REQUEST['tree-ish'];
        if (empty($tree)) {
            $this->renderNotFound("No tree-ish provided");
            return;
        }

        $compare_with = $_REQUEST['compare-with'] ?? $tree . '^';

        $diff = $this->getProject()->GetDiffTree($compare_with, $tree);

        $diff_lines = [];
        if (!empty(trim($diff))) {
            $diff_lines = explode("\n", $diff);
        }

        $response = [];

        foreach ($diff_lines as $diff_line) {
            list($change, $file) = explode("\t", $diff_line);
            list($old_mode, $new_mode, $old_blob, $new_blob, $status) = explode(" ", $change);
            $old_mode = ltrim($old_mode, ":");

            $response[] = [
                'file'     => $file,
                'old_mode' => $old_mode,
                'new_mode' => $new_mode,
                'status'   => $status,
                'old_blob' => $old_blob,
                'new_blob' => $new_blob,
            ];
        }

        $this->sendResponse(
            [
                'diff' => $response
            ]
        );
    }

    protected function handleMergeBaseRequest()
    {
        $first = $_REQUEST['first-commit'] ?? false;
        $second = $_REQUEST['second-commit'] ?? false;

        if (!$first || !$second) {
            $this->renderNotFound("Need to specify 'first-commit' and 'second-commit'");
        }

        $Commit = $this->getProject()->getMergeBase($first, $second);

        if (!$Commit) {
            $this->renderNotFound("Can't find merge-base for requested commits");
            return;
        }

        $this->sendResponse(['hash' => $Commit->GetHash()]);
    }

    protected function renderNotFound($custom_error = "")
    {
        $error = $custom_error ? $custom_error : "Resource not found";
        $this->sendResponse(['error' => $error], 404);
    }

    protected function sendResponse(array $message, $response_code = 200)
    {
        http_response_code($response_code);
        echo json_encode($message);
    }

    /**
     * @param \GitPHP\Git\Commit $Commit
     * @return array
     */
    protected function renderCommit(\GitPHP\Git\Commit $Commit) : array
    {
        $commit_info = [
            'hash'         => $Commit->GetHash(),
            'message'      => join("\n", $Commit->GetComment()),
            'author'       => $Commit->GetAuthorName(),
            'author_email' => $Commit->GetAuthorEmail(),
            'time'         => $Commit->GetAuthorEpoch(),
        ];

        $include_info = $_REQUEST["include"] ?? [];
        if (!is_array($include_info)) {
            $include_info = [$include_info];
        }

        foreach ($include_info as $item) {
            switch ($item) {
                case 'name-status':
                    $commit_info['name-status'] = array_map(
                        function (\GitPHP\Git\FileDiff $FileDiff) {
                            return ['file' => $FileDiff->GetFromFile(), 'status' => $FileDiff->GetStatus()];
                        },
                        $Commit->DiffToParent()->ToArray()
                    );
                    break;
            }
        }
        return $commit_info;
    }

    protected function handleContributorsRequest()
    {
        $file = $_REQUEST['file'] ?? '';

        if (empty($file)) {
            $this->renderNotFound("Need to specify 'file'");
        }

        $contributors = [];
        $log = $this->getProject()->GetLog('HEAD', 1000, 0, null, ['file' => $file]);
        foreach ($log as $Commit) {
            if (isset($contributors[$Commit->GetAuthorEmail()])) {
                $contributors[$Commit->GetAuthorEmail()]['commits_count']++;
            } else {
                $contributors[$Commit->GetAuthorEmail()] = [
                    'name' => $Commit->GetAuthorName(),
                    'email' => $Commit->GetAuthorEmail(),
                    'commits_count' => 1,
                ];
            }
        }

        $this->sendResponse(array_values($contributors));
    }

    protected function handleProjectRequest()
    {
        $project = $_REQUEST['project'] ?? '';
        if (empty($project)) {
            $this->renderNotFound('Need to specify project');
            return;
        }

        // one day probably we'll need to check if this is admin request
        // but for now it's enough to check if repository creation is enabled
        // for mere mortals
        $allow_create_projects = \GitPHP\Config::GetInstance()->GetValue(
            \GitPHP\Config::ALLOW_USER_CREATE_REPOS,
            false
        );
        if ($this->isPostRequest()) {
            if ($allow_create_projects) {
                if (!$this->createProjectFromRequestData()) {
                    return;
                }
            } else {
                $this->renderNotFound("Repository creation is forbidden");
                return;
            }
        }

        try {
            $Model = new \GitPHP\Model_Gitosis();
            $project_data = $Model->getRepositoryByProject($project);
            if (empty($project_data)) {
                $this->renderNotFound("Cannot find project {$project}");
                return;
            }

            $response = [
                'project' => $project_data['project'],
                'description' => $project_data['description'],
                'category' => $project_data['category'],
            ];
            $clone_url = \GitPHP\Util::AddSlash(\GitPHP\Config::GetInstance()->GetValue('cloneurl', ''), false);
            if ($clone_url) {
                $response['clone_url'] = $clone_url . $project_data['project'];
            }
            $this->sendResponse($response);
        } catch (\Exception $e) {
            $this->renderNotFound("Cannot find project {$project}");
        }
    }

    protected function createProjectFromRequestData() : bool
    {
        $yes_no_options = ['Yes', 'No'];

        $project = empty($_POST['project']) || !is_string($_POST['project']) ? '' : $_POST['project'];
        $project = trim($project);

        $description = empty($_POST['description']) || !is_string($_POST['description']) ? '' : $_POST['description'];
        $description = trim($description);

        $category = empty($_POST['category']) || !is_string($_POST['category']) ? '' : $_POST['category'];
        $category = trim($category);

        $notify_email = empty($_POST['notify_email']) || !is_string($_POST['notify_email']) ? '' : $_POST['notify_email'];
        $notify_email = trim($notify_email);

        $display = empty($_POST['display']) || !in_array($_POST['display'], $yes_no_options) ? '' : $_POST['display'];
        $display = trim($display);

        $restricted = empty($_POST['restricted']) || !in_array($_POST['restricted'], $yes_no_options) ? '' : $_POST['restricted'];
        $restricted = trim($restricted);

        if (!$project) {
            $this->sendResponse(['error' => 'Project can not be empty.'], 400);
            return false;
        }

        if (!preg_match('/\.git$/', $project)) {
            $this->sendResponse(['error' => 'Project name must end with ".git".'], 400);
            return false;
        }

        $Model = new \GitPHP\Model_Gitosis();

        // this will allow us to have slightly better error messages
        $previous_project = $Model->getRepositoryByProject($project);
        if (!empty($previous_project)) {
            $this->sendResponse(['error' => 'Repository with this name already exists'], 400);
            return false;
        }

        $Session = \GitPHP\Session::instance();
        $result = $Model->addRepository(
            $project,
            $description,
            $category,
            $notify_email,
            $restricted,
            $display,
            $Session->getUser()->getEmail() ?? $Session->getUser()->getName()
        );
        if (!$result) {
            $this->sendResponse(['error' => 'Cannot create project: ' . $Model->getLastError()], 400);
            return false;
        }
        //creating the repo
        if (\GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::UPDATE_AUTH_KEYS_FROM_WEB)) {
            $base_path = \GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::PROJECT_ROOT);
            exec("cd " . $base_path . ";git init --bare " . escapeshellarg($project), $out, $retval);
            if ($retval) {
                $this->sendResponse(['error' => 'Can\'t init bare repo in ' . $base_path], 500);
                return false;
            }
        }
        return true;
    }

    private function isPostRequest() : bool
    {
        return isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'POST');
    }
}
