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
            $limit = $_REQUEST['limit'] ?? 1000;

            $this->sendResponse(
                [
                    'commits' => array_map(
                        function (\GitPHP_Commit $Commit) { return $this->renderCommit($Commit); },
                        $this->getProject()->GetLog($range_to, $limit, 0, $range_from)
                    )
                ]
            );

            return;
        }

        $this->renderNotFound("Nothing to get log for");
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
}
