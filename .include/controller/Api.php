<?php
/**
 * @team QA <qa@corp.badoo.com>
 * @maintainer Aleksandr Izmaylov <a.izmaylov@corp.badoo.com>
 */

namespace GitPHP\Controller;

class Api implements ControllerInterface
{
    protected $action;
    protected $project;

    public function Render()
    {
        $this->renderCommonHeaders();

        $Session = \GitPHP_Session::instance();
        if (!$Session->isAuthorized()) {
            $this->sendResponse(['error' => "Unauthorized api usage is forbidden"], 403);
            return;
        }

        if (!$this->getProject()) {
            $this->renderNotFound();
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
        if (!isset($this->action)) {
            // uri should be in form /api/project.git/action
            $this->action = explode(".git/", $_SERVER['DOCUMENT_URI'], 2)[1] ?? "";
        }
        return $this->action;
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
                    $this->project = \GitPHP_ProjectList::GetInstance()->GetProject(substr($last_uri_part, 0, $dot_git_pos + 4));
                } catch (\Exception $e) {
                    $this->project = false;
                }
            }
        }
        return $this->project;
    }

    protected function handleCommitRequest()
    {
        $search_for = $_REQUEST["hash"] ?? false;
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
                        function (\GitPHP_Commit $Commit) { return $this->renderCommit($Commit); },
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
                    function (\GitPHP_Commit $Commit) { return $this->renderCommit($Commit); },
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
     * @param \GitPHP_Commit $Commit
     * @return array
     */
    protected function renderCommit(\GitPHP_Commit $Commit) : array
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
                        function (\GitPHP_FileDiff $FileDiff) {
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
}
