<?php
namespace GitPHP\Controller;

class Comment extends Base
{
    const STATUS_OK = 0;
    const STATUS_WRONG_PARAMS = 1;
    const STATUS_ERR = 2;
    const STATUS_SNAPSHOT_EXISTS = 10;

    /**
     * @var array
     */
    private $response;

    /**
     * @var \GitPHP_Db
     */
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \GitPHP_Db::getInstance();
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    protected function LoadData() {}

    protected function redirect($url, $code = 302)
    {
        $result = array('action' => 'redirect', 'url' => $url, 'code' => $code);
        echo json_encode($result);
        die;
    }

    protected function GetTemplate() {}

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
        return 'comment';
    }

    protected function ReadQuery() {}

    public function setResponse($key, $value)
    {
        $this->response[$key] = $value;
        return $this;
    }

    public function Render()
    {
        $this->response = array();

        switch ($_GET['a']) {
            case 'save_comment':
                $this->saveCommentAction();
                break;

            case 'get_review':
                $this->getReviewAction();
                break;

            case 'set_review_status':
                $this->setReviewStatusAction();
                break;

            case 'get_comments':
                $this->getCommentsAction();
                break;

            case 'delete_comment':
                $this->deleteCommentAction();
                break;

            case 'get_unfinished_review':
                $this->getLastUnfinishedReview();
                break;

            case 'delete_all_draft_comments':
                $this->deleteAllDraftComments();
                break;

            default:
                $this->response = array('status' => self::STATUS_WRONG_PARAMS, 'error' => 'Wrong params');
        }

        header('Content-Type: application/json; charset=UTF-8');

        $this->setResponse('log', \GitPHP_Log::GetInstance()->getForJson());

        echo json_encode($this->response);
        die;
    }

    public function saveCommentAction()
    {
        $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
        $ticket = trim($_POST['ticket']);
        $repo = trim($_POST['repo']);
        $hash = trim($_POST['hash']);
        $hash_base = isset($_POST['hash_base']) ? trim($_POST['hash_base']) : '';
        $file = isset($_POST['file']) ? trim($_POST['file']) : '';
        $line = trim($_POST['line']);
        $text = trim($_POST['text']);
        $realLine = isset($_POST['real_line']) ? trim($_POST['real_line']) : '';
        $realLineBefore = isset($_POST['real_line_before']) ? trim($_POST['real_line_before']) : '';
        $linesCount = isset($_POST['lines_count']) ? $_POST['lines_count'] : '';
        $side = isset($_POST['side']) ? $_POST['side'] : null;

        $review_type = $side ? 'sidebyside' : 'unified';

        if (empty($repo)) {
            $this->setResponse('error', 'Repo not detected');
            return;
        } else if (empty($hash)) {
            $this->setResponse('error', 'Commit hash not detected');
            return;
        } else if ($hash_base != 'blob' && empty($file)) {
            $this->setResponse('error', 'Filename not detected');
            return;
        } else if (empty($line)) {
            $this->setResponse('error', 'File line not detected');
            return;
        } else if (empty($text)) {
            $this->setResponse('error', 'Please enter comment text');
            return;
        }

        if (!$review_id) {
            if (empty($ticket)) {
                $this->setResponse('error', 'Enter review name or ticket key on the page bottom');
                return;
            }
            $review_id = $this->db->addReview($ticket);
            $this->Session->set(\GitPHP_Session::SESSION_REVIEW_ID, $review_id);
        }

        $snapshot = $this->db->findSnapshotByHashAndReview($review_id, $hash, $hash_base, $review_type);
        if (!$snapshot) {
            $snapshot['id'] = $this->db->addSnapshot($review_id, $repo, $hash, $hash_base, $review_type);
        } else {
            /* review_type was specified so there should be only one snapshot */
            $snapshot = reset($snapshot);
        }

        $author = $this->Session->getUser()->getId();

        $comment = $this->db->findComment($snapshot['id'], $author, $file, $line, 'Draft', $side);
        if ($comment) {
            $comment = reset($comment);
            $id = $this->db->updateComment($comment['id'], $text);
        } else {
            $id = $this->db->addComment($snapshot['id'], $author, $file, $line, $text, $realLine, $realLineBefore, $linesCount, $side);
        }

        $this->setResponse('status', self::STATUS_OK)
            ->setResponse('review_id', $review_id)
            ->setResponse('comment_id', $id);
    }

    public function deleteCommentAction()
    {
        $comment_id = (int)$_POST['comment_id'];
        if (!empty($comment_id)) {
            $this->db->updateCommentStatus($comment_id, 'Deleted');
        }
        $this->setResponse('status', self::STATUS_OK)->setResponse('comment_id', $comment_id);
    }

    public function getReviewAction()
    {
        $ticket = '';
        $url = isset($_POST['url']) ? $_POST['url'] : '';
        if ($key = \GitPHP\Tracker::instance()->parseTicketFromString($url)) {
            $ticket = \GitPHP\Tracker::instance()->getReviewTicketPrefix() . $key;
        }
        $commit_message = isset($_POST['commit_message']) ? $_POST['commit_message'] : '';
        if ($key = \GitPHP\Tracker::instance()->parseTicketFromString($commit_message)) {
            $ticket = \GitPHP\Tracker::instance()->getReviewTicketPrefix() . $key;
        }
        $hash = $_POST['hash'];

        $session_review_id = $this->Session->get(\GitPHP_Session::SESSION_REVIEW_ID);

        $review_list = $this->db->getReview($ticket, $hash, $session_review_id);

        $comments_count = $this->db->getCommentsCountForReviews(array_keys($review_list), $this->Session->getUser()->getId());
        foreach ($comments_count as $review_id => $row) {
            $review_list[$review_id]['comments_count'] = $row['cnt'];
            $review_list[$review_id]['comments_count_draft'] = (int)$row['cnt_draft'];
            $review_list[$review_id]['ticket'] = $row['ticket'];
        }

        $new_review = true;
        if (isset($review_list[$session_review_id])
            && in_array($review_list[$session_review_id]['origin'], ['ticket', 'hash'])) {
            $review_list[$session_review_id]['selected'] = true;
            $new_review = false;
        }
        $review_list = array_values($review_list);

        $this->setResponse('status', self::STATUS_OK)
            ->setResponse('reviews', $review_list)
            ->setResponse('new_review_name', $ticket)
            ->setResponse('new_review', $new_review);
    }

    public function setReviewStatusAction()
    {
        $reviewId = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : '';

        if (!in_array($status, array('Deleted', 'Finish'))) {
            $this->setResponse('error', 'Wrong status');
            return;
        }

        $author = $this->Session->getUser()->getId();

        $review = $this->db->findReviewById($reviewId);
        if (!$review) {
            $this->setResponse('error', 'Review not found');
            return;
        }

        if (!$this->db->setReviewCommentsStatusByAuthor($reviewId, $author, 'Draft', $status)) {
            $this->setResponse('error', 'Set status failed');
            return;
        }

        if ($status == 'Finish' && $this->db->getAffectedRows()) {
            $comments = $this->db->getCommentsByReviewAndAuthor($reviewId, $author);
            $url = Review::getReviewUrl($reviewId);
            $review_type = $comments[0]['review_type'];
            \GitPHP_Util::sendReviewEmail($this->Session->getUser()->getEmail(), $review['ticket'], $url, $comments, $review_type);
            if (\GitPHP\Tracker::instance()->enabled()) {
                \GitPHP_Util::addReviewToTracker($this->Session->getUser()->getId(), $review['ticket'], $url, $comments, $review_type);
            }
        }

        $this->setResponse('status', self::STATUS_OK);
    }

    public function getCommentsAction()
    {
        /* mandatory params */
        $params = ['review_id', 'hash', 'hash_base'];
        foreach ($params as $param) {
            if (!isset($_REQUEST[$param])) {
                $this->setResponse('status', self::STATUS_WRONG_PARAMS);
                return;
            }
        }
        $review_id = (int)$_REQUEST['review_id'];

        /* optional params */
        $file = isset($_REQUEST['file']) ? trim($_REQUEST['file']) : '';

        $snapshots = $this->db->findSnapshotByHashAndReview($review_id, $_REQUEST['hash'], $_REQUEST['hash_base']);
        if ($snapshots) {
            $author = $this->Session->getUser()->getId();
            $review = $this->db->findReviewById($review_id);
            $commentsCount = (int)$this->db->getCommentsCount($review_id);

            $comments = [];
            $last_snapshot_with_comments = null;
            foreach ($snapshots as $snapshot) {
                $comments_from_snapshot = $this->db->getCommentsBySnapshotAndAuthor($snapshot['id'], $author, $file);
                if ($comments_from_snapshot
                    && (!$last_snapshot_with_comments || $last_snapshot_with_comments['created'] < $snapshot['created'])) {
                    $last_snapshot_with_comments = $snapshot;
                }
                foreach ($comments_from_snapshot as $comment) {
                    $date = strtotime($comment['date']);
                    $format = date('Y') == date('Y', $date) ? 'j M G:i' : 'j M Y G:i';
                    $comment['date'] = date($format, $date);
                    $comments[] = $comment;
                }
            }
            $this->setResponse('comments', $comments)
                ->setResponse('review', $review)
                ->setResponse('snapshot', $last_snapshot_with_comments)
                ->setResponse('comments_count', $commentsCount);
        }
    }

    public function getLastUnfinishedReview()
    {
        $draft_messages = $this->db->getDraftCommentByAuthor($this->Session->getUser()->getId());
        if ($draft_messages && count($draft_messages) > 0) {
            $last_message = array_shift($draft_messages);
            if (isset($last_message['repo']) && isset($last_message['hash_head']) && isset($last_message['hash_base']) && isset($last_message['review_id'])) {
                $this->setResponse('last_review', Review::getReviewUrl($last_message['review_id']));
            }
        }

        $this->setResponse('status', self::STATUS_OK);
    }

    public function deleteAllDraftComments()
    {
        $result = $this->db->deleteAllDraftComments($this->Session->getUser()->getId());
        if (!$result) {
            $this->setResponse('status', self::STATUS_ERR);
        } else {
            $this->setResponse('status', self::STATUS_OK);
        }
    }
}
