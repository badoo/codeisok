<?php
namespace GitPHP\Controller;

class Review extends Base
{
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
        return 'review.tpl';
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
        return 'review';
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
        $this->params['review'] = isset($_GET['review']) ? (int)$_GET['review'] : 0;
        $this->params['comment'] = isset($_GET['c']) ? (int)$_GET['c'] : 0;
        $this->params['max_id'] = isset($_GET['max_id']) ? (int)$_GET['max_id'] : 0;
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
        $db = \GitPHP_Db::getInstance();

        $to_start_link = $more_link = null;
        if ($this->params['review']) {
            $snapshots = $db->getSnapshotListByReview($this->params['review']);
        } else {
            $limit = 50;
            $snapshots = $db->getSnapshotList($limit + 1, $this->params['max_id']);
            if ($this->params['max_id']) {
                $to_start_link = \GitPHP_Application::getUrl('reviews', ['max_id' => 0]);
            }
            if (count($snapshots) > $limit) {
                $last = array_pop($snapshots);
                $more_link = \GitPHP_Application::getUrl('reviews', ['max_id' => $last['id']]);
            }
        }
        $this->tpl->assign('to_start_link', $to_start_link);
        $this->tpl->assign('more_link', $more_link);
        $c = '';
        if (!empty($this->params['comment'])) {
            $c = '#' . $this->params['comment'];
        }
        if (is_array($snapshots) && count($snapshots) == 1) {
            $snapshot = reset($snapshots);
            $comment = null;
            if ($snapshot['hash_base'] == 'blob') {
                $comments = $db->getComments($snapshot['id']);
                $comment = reset($comments);
            }
            $url = \GitPHP_Util::getReviewLink($snapshot, $comment['file']);

            $this->redirect($url . $c);
        }

        $review_ids = array_unique(array_column($snapshots, 'review_id'));
        $reviews = $db->getReviewList($review_ids);
        $commentsCount = $db->getCommentsCountForReviews($review_ids, $this->Session->getUser()->getId());
        foreach ($snapshots as &$snapshot) {
            $snapshot['ticket'] = isset($reviews[$snapshot['review_id']]) ? $reviews[$snapshot['review_id']] : '';
            $ticket_key = $reviews[$snapshot['review_id']];
            $snapshot['ticket_url'] = '';
            if (\GitPHP\Tracker::instance()->enabled()) {
                $ticket_key = \GitPHP\Tracker::instance()->parseTicketFromString($ticket_key);
                if (!empty($ticket_key)) {
                    $snapshot['ticket_url'] = \GitPHP\Tracker::instance()->getTicketUrl($ticket_key);
                }
            }
            $snapshot['count'] = (isset($commentsCount[$snapshot['review_id']]) ? $commentsCount[$snapshot['review_id']]['cnt'] : '')
                . (!empty($commentsCount[$snapshot['review_id']]['cnt_draft']) ? '+' . $commentsCount[$snapshot['review_id']]['cnt_draft'] . ' draft' : '');
            if ($snapshot['hash_base'] == 'blob') {
                $comments = $db->getComments($snapshot['id']);
                $comment = reset($comments);
                $snapshot['file'] = $comment['file'];
            }
            $snapshot['url'] = \GitPHP_Util::getReviewLink($snapshot, $snapshot['file'] ?? null);

            if ($snapshot['hash_base'] == 'blob') {
                $snapshot['title'] = $snapshot['hash_head'] . ' ' . $snapshot['file'];
            } else if ($snapshot['hash_base']) {
                $snapshot['title'] = $snapshot['hash_head'] . ' - ' . $snapshot['hash_base'];
            } else {
                $snapshot['title'] = $snapshot['hash_head'];
            }
        }

        $this->tpl->assign('snapshots', $snapshots);
    }

    public static function getReviewUrl($reviewId)
    {
        $hostname = \GitPHP_Util::getHostnameUrl();
        $url = $hostname . '/r/' . $reviewId;
        return $url;
    }
}
