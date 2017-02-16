<?php
class GitPHP_Db
{
    const TBL_HEADS = 'Heads';
    const TBL_COMMENT = 'Comment';
    const TBL_SNAPSHOT = 'Snapshot';
    const TBL_REVIEW = 'Review';
    const TBL_USER       = 'User';
    const TBL_ACCESS     = 'Access';
    const TBL_REPOSITORY = 'Repository';

    const QUERY_SAVE_BRANCH_HEAD = 'INSERT INTO #TBL_HEADS# SET branch = #branch#, hash = #hash# ON DUPLICATE KEY UPDATE hash = VALUES(hash)';
    const QUERY_GET_BRANCH_HEAD = 'SELECT hash FROM #TBL_HEADS# WHERE branch = #branch#';
    const QUERY_GET_DRAFT_COMMENT_BY_AUTHOR = 'SELECT * FROM #TBL_COMMENT# rc
       JOIN #TBL_SNAPSHOT# rs ON rs.id = rc.snapshot_id
       WHERE rc.`status` = \'Draft\' AND rc.`author` = #author#
       ORDER BY `date` desc limit #limit#';
    const QUERY_GET_COMMENTS_BY_REVIEW_AUTHOR = 'SELECT rc.id, rc.snapshot_id, rc.author, rc.date, rc.file, rc.line,
            rc.text, rc.status, rc.real_line, rc.real_line_start, rc.real_line_before, rc.real_line_before_start,
            rc.lines_count, rc.side, rs.id as snapshot_id, rs.review_id, rs.hash_head, rs.hash_base, rs.repo, rs.created,
            rs.review_type
        FROM #TBL_COMMENT# rc
             JOIN #TBL_SNAPSHOT# rs ON rs.id = rc.snapshot_id
         WHERE `review_id` = #review_id# AND (`status` = "Finish" OR (`author` = #author# AND `status` != "Deleted"))
         ORDER BY rc.`file`, rc.`real_line_before`, rc.`real_line`, rc.`date`';
    const QUERY_GET_COMMENTS_BY_SNAPSHOT_AUTHOR = 'SELECT rc.id, rc.snapshot_id, rc.author, rc.date, rc.file, rc.line,
            rc.text, rc.status, rc.real_line, rc.real_line_start, rc.real_line_before, rc.real_line_before_start,
            rc.lines_count, rc.side
            FROM #TBL_COMMENT# rc
            WHERE `snapshot_id` = #snapshot_id#
                AND (`status` = "Finish" OR (`author` = #author# AND `status` != "Deleted"))
                #PART_COMMENT_FILE#
            ORDER BY rc.`file`, rc.`real_line_before`, rc.`real_line`, rc.`date`';
    const PART_COMMENT_FILE = 'AND `file` = #file#';

    const QUERY_GET_COMMENTS_BY_SNAPSHOT = 'SELECT rc.id, rc.snapshot_id, rc.author, rc.date, rc.file, rc.line, rc.text,
        rc.status, rc.real_line, rc.real_line_start, rc.real_line_before, rc.real_line_before_start, rc.lines_count
        FROM #TBL_COMMENT# rc
        WHERE `snapshot_id` = #snapshot_id# AND `status` != "Deleted"
        ORDER BY rc.`file`, rc.`real_line_before`, rc.`real_line`, rc.`date`';

    const QUERY_SET_REVIEW_STATUS = 'UPDATE #TBL_COMMENT# rc JOIN #TBL_SNAPSHOT# rs ON rc.snapshot_id = rs.id
        SET `status` = #new_status# WHERE `author` = #author# AND `review_id` = #review_id# #PART_STATUS#';
    const PART_REVIEW_STATUS = 'AND `status` = #status#';

    const QUERY_ADD_REVIEW = 'INSERT INTO #TBL_REVIEW# SET `ticket` = #ticket#, `created` = NOW()';

    const QUERY_FIND_SNAPSHOTS = 'SELECT * FROM #TBL_SNAPSHOT# WHERE `hash_head` IN (#hash_head#)';

    const QUERY_FIND_SNAPSHOT = 'SELECT * FROM #TBL_SNAPSHOT# WHERE `hash_head` = #hash_head#';

    const QUERY_FIND_SNAPSHOT_BY_HASHREVIEW = 'SELECT * FROM #TBL_SNAPSHOT#
        WHERE `hash_head` = #hash_head# AND `review_id` = #review_id# AND `hash_base` = #hash_base# #PART_SNAPSHOT_TYPE#';
    const PART_SNAPSHOT_TYPE = ' AND `review_type` = #review_type#';

    const QUERY_FIND_SNAPSHOT_BY_TICKET = 'SELECT rs.* FROM #TBL_SNAPSHOT# rs JOIN #TBL_REVIEW# rr ON rs.review_id = rr.id WHERE rr.ticket = #ticket#';

    const QUERY_FIND_REVIEW = 'SELECT * FROM #TBL_REVIEW# WHERE `id` = #id#';

    const QUERY_GET_REVIEWS_BY_IDS = 'SELECT `id`, `ticket` FROM #TBL_REVIEW# WHERE `id` IN (#ids#) ORDER BY `id` DESC LIMIT 100';

    const QUERY_GET_REVIEWS = 'SELECT `id`, `ticket` FROM #TBL_REVIEW# ORDER BY `id` DESC LIMIT 100';

    const QUERY_GET_COMMENTSCOUNT_FOR_REVIEWS = 'SELECT rs.review_id, SUM(rc.status = "Finish") cnt,
            SUM(rc.status = "Draft" AND rc.author = #author#) cnt_draft, rr.ticket
        FROM #TBL_SNAPSHOT# rs
        JOIN #TBL_COMMENT# rc ON rs.id = rc.snapshot_id
        JOIN #TBL_REVIEW# rr ON rs.review_id = rr.id
        WHERE rs.review_id IN (#review_id#)
        GROUP BY rs.review_id';

    const QUERY_GET_COMMENTS_COUNT = 'SELECT SUM(rc.status = "Finish") cnt
            FROM #TBL_SNAPSHOT# rs
            JOIN #TBL_COMMENT# rc ON rs.id = rc.snapshot_id
            JOIN #TBL_REVIEW# rr ON rs.review_id = rr.id
            WHERE rs.review_id = #review_id#
            GROUP BY rs.review_id';

    const QUERY_GET_SNAPSHOTS = 'SELECT * FROM #TBL_SNAPSHOT# #LIST_PART# ORDER BY `id` DESC LIMIT #limit#';
    const PART_SNAPSHOTS_LIST = 'WHERE id <= #id#';

    const QUERY_GET_SNAPSHOTS_BY_REVIEW = 'SELECT * FROM #TBL_SNAPSHOT# WHERE review_id = #review_id# ORDER BY `id` DESC';

    const QUERY_FIND_COMMENT = 'SELECT * FROM #TBL_COMMENT#
            WHERE `snapshot_id` = #snapshot_id# AND `author` = #author# AND `file` = #file#
            AND `line` = #line# AND `status` IN (#status#) #PART_SIDE#';
    const PART_COMMENT_NOSIDE = ' AND `side` IS NULL';
    const PART_COMMENT_SIDE_SOME = ' AND `side` IS NOT NULL';
    const PART_COMMENT_SIDE = ' AND `side` = #side#';
    const QUERY_UPDATE_COMMENT_STATUS = 'UPDATE #TBL_COMMENT# SET `status` = #status# WHERE `id` = #id#';

    const QUERY_UPDATE_COMMENT = 'UPDATE #TBL_COMMENT# SET `text` = #text# WHERE `id` = #id#';

    const QUERY_ADD_SNAPSHOT = 'INSERT INTO #TBL_SNAPSHOT#
            SET `review_id` = #review_id#, `hash_head` = #hash_head#, `hash_base` = #hash_base#, `repo` = #repo#,
            `created` = NOW(), `review_type` = #review_type#';

    const QUERY_ADD_COMMENT = 'INSERT INTO #TBL_COMMENT#
            SET `snapshot_id` = #snapshot_id#, `author` = #author#, `file` = #file#, `line` = #line#,
            `text` = #text#, `date` = NOW(), `lines_count` = #lines_count#, `real_line` = #real_line#,
            `real_line_before` = #real_line_before# #PART_SIDE#';
    const PART_ADDCOMMENT_SIDE = ', `side` = #side#';

    const QUERY_DELETE_ALL_DRAFT_COMMENTS = 'UPDATE #TBL_COMMENT# SET `status` = "Deleted" WHERE `author` = #author# AND status = "Draft"';

    private $db;

    private $link;

    private $errno;

    private $error;

    private $numRows;

    private $affectedRows;

    private $insert_id;

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance) {
            return self::$instance;
        }
        return self::$instance = new self();
    }

    public function __construct()
    {
        $config   = GitPHP_Config::GetInstance();
        $host     = $config->GetValue(\GitPHP_Config::DB_HOST);
        $user     = $config->GetValue(\GitPHP_Config::DB_USER);
        $password = $config->GetValue(\GitPHP_Config::DB_PASSWORD);
        $this->db = $config->GetValue(\GitPHP_Config::DB_NAME);
        if (substr($host, 0, 1) == ':') {
            $this->link = mysqli_connect(null, $user, $password, '', 0, substr($host, 1));
        } else {
            $this->link = mysqli_connect($host, $user, $password);
        }
        if (!$this->link) {
            GitPHP_Log::GetInstance()->Log('mysqli_connect', "$user@$host");
        }
    }

    /**
     * @param $sql
     * @param $params
     * @return bool|\GitPHP\Db_Result
     */
    public function query($sql, $params)
    {
        $sql = $this->bind($sql, $params);
        GitPHP_Log::GetInstance()->timerStart();
        $result = mysqli_query($this->link, $sql);
        GitPHP_Log::GetInstance()->timerStop('mysqli_query', $sql);

        if (!$result) {
            $this->errno = mysqli_errno($this->link);
            $this->error = mysqli_error($this->link);
            $msg = "db_error: $this->errno:$this->error " . (new \Exception());
            trigger_error($msg);
            GitPHP_Log::GetInstance()->Log('mysqli_error', "$this->errno: $this->error");
        } else {
            if (is_resource($result)) $this->numRows = mysqli_num_rows($result);
            $this->affectedRows = mysqli_affected_rows($this->link);
            $this->insert_id = mysqli_insert_id($this->link);
            $result = new \GitPHP\Db_Result($result);
        }
        return $result;
    }

    public function quote($value, $implode = false)
    {
        if (is_array($value)) {
            foreach ($value as &$valueItem) {
                $valueItem = $this->quote($valueItem, true);
            }
            if ($implode) {
                $value = implode(',', $value);
            }
        } else if (!is_int($value)) {
            $value = '"' . mysqli_real_escape_string($this->link, $value) . '"';
        }
        return $value;
    }

    public function bind($sql, array $params = [])
    {
        $tables = $this->getTablesAsDbwrapper();
        $params += $tables;

        $pieces = explode('#', $sql);
        $result = '';
        $sharp = 0;
        $count = count($pieces);
        foreach ($pieces as $index => $piece) {
            if ($index % 2 == $sharp) {
                $result .= $piece;
            } else if (isset($params[$piece]) && $index != 0 && $index != $count - 1) {
                $result .= $params[$piece];
            } else {
                $result .= '#' . $piece;
                $sharp = $sharp ? 0 : 1;
            }
        }
        return $result;
    }

    public function getAffectedRows()
    {
        return $this->affectedRows;
    }

    public function addComment($snapshotId, $author, $file, $line, $text, $realLine, $realLineBefore, $linesCount, $side = null)
    {
        $sql = str_replace('#PART_SIDE#', in_array($side, ['lhs', 'rhs']) ? self::PART_ADDCOMMENT_SIDE : '', self::QUERY_ADD_COMMENT);

        $params = [
            'snapshot_id' => (int)$snapshotId,
            'author' => $this->quote(htmlspecialchars($author)),
            'file' => $this->quote(htmlspecialchars($file)),
            'line' => (int)$line,
            'text' => $this->quote(htmlspecialchars($text)),
            'lines_count' => (int)$linesCount,
            'real_line' => (int)$realLine,
            'real_line_before' => (int)$realLineBefore,
            'side' => $this->quote($side),
        ];
        $res = $this->query($sql, $params);
        return $res ? $this->insert_id : $res;
    }

    public function updateCommentStatus($id, $status)
    {
        $res = $this->query(self::QUERY_UPDATE_COMMENT_STATUS, ['id' => (int)$id, 'status' => $this->quote($status)]);
        return $res ? $this->insert_id : $res;
    }

    public function updateComment($id, $text)
    {
        $res = $this->query(self::QUERY_UPDATE_COMMENT, ['id' => (int)$id, 'text' => $this->quote(htmlspecialchars($text))]);
        return $res ? $this->insert_id : $res;
    }

    public function addReview($ticket)
    {
        $ticket = htmlspecialchars($ticket);
        $res = $this->query(self::QUERY_ADD_REVIEW, ['ticket' => $this->quote($ticket)]);
        return $res ? $this->insert_id : $res;
    }

    public function addSnapshot($review_id, $repo, $hash, $hash_base = '', $review_type = 'unified')
    {
        $params = [
            'review_id' => (int)$review_id,
            'hash_head' => $this->quote($hash),
            'hash_base' => $this->quote($hash_base),
            'repo' => $this->quote($repo),
            'review_type' => $this->quote($review_type),
        ];
        $res = $this->query(self::QUERY_ADD_SNAPSHOT, $params);
        return $res ? $this->insert_id : $res;
    }

    public function findSnapshotByHash($hash)
    {
        return $this->getAll(self::QUERY_FIND_SNAPSHOT, ['hash_head' => $this->quote($hash)]);
    }

    public function findSnapshotsByHash($hash)
    {
        return $this->getAssoc(self::QUERY_FIND_SNAPSHOTS, ['hash_head' => $this->quote($hash)], 'hash_head');
    }

    public function findSnapshotByHashAndReview($review_id, $hash, $hash_base, $review_type = '')
    {
        $params = [
            'review_id' => (int)$review_id,
            'hash_head' => $this->quote($hash),
            'hash_base' => $this->quote($hash_base),
            'review_type' => $this->quote($review_type),
        ];
        $result = $this->getAll(
            $this->bind(
                self::QUERY_FIND_SNAPSHOT_BY_HASHREVIEW,
                ['PART_SNAPSHOT_TYPE' => in_array($review_type, ['unified', 'sidebyside']) ? self::PART_SNAPSHOT_TYPE : '']
            ),
            $params
        );

        return $result;
    }

    public function findSnapshotByTicket($ticket)
    {
        $result = $this->getAll(self::QUERY_FIND_SNAPSHOT_BY_TICKET, ['ticket' => $this->quote($ticket)]);
        return $result;
    }

    public function findReviewById($id)
    {
        $result = $this->getRow(self::QUERY_FIND_REVIEW, ['id' => (int)$id]);
        return $result;
    }

    public function getReviewList($review_ids = [])
    {
        if ($review_ids) {
            $review_ids = array_filter(array_map('intval', $review_ids));
            $sql = self::QUERY_GET_REVIEWS_BY_IDS;
        } else {
            $sql = self::QUERY_GET_REVIEWS;
        }
        return $this->getAssoc($sql, ['ids' => $review_ids], 'id');
    }

    public function getComments($snapshotId)
    {
        return $this->getAll(self::QUERY_GET_COMMENTS_BY_SNAPSHOT, ['snapshot_id' => (int)$snapshotId]);
    }

    public function getCommentsBySnapshotAndAuthor($snapshotId, $author, $file = '')
    {
        $params = ['snapshot_id' => (int)$snapshotId, 'author' => $this->quote($author), 'file' => $this->quote(htmlspecialchars($file))];
        return $this->getAll(
            $this->bind(
                self::QUERY_GET_COMMENTS_BY_SNAPSHOT_AUTHOR,
                ['PART_COMMENT_FILE' => $file ? self::PART_COMMENT_FILE : '']
            ),
            $params
        );
    }

    public function getCommentsByReviewAndAuthor($reviewId, $author)
    {
        return $this->getAll(self::QUERY_GET_COMMENTS_BY_REVIEW_AUTHOR, ['review_id' => (int)$reviewId, 'author' => $this->quote($author)]);
    }

    public function getDraftCommentByAuthor($author, $limit = 10)
    {
        return $this->getAll(self::QUERY_GET_DRAFT_COMMENT_BY_AUTHOR, ['author' => $this->quote($author), 'limit' => (int)$limit]);
    }

    public function getCommentsCountForReviews($review_ids, $author)
    {
        $review_ids = array_filter(array_map('intval', $review_ids));
        if (empty($review_ids)) {
            return [];
        }
        return $this->getAssoc(
            self::QUERY_GET_COMMENTSCOUNT_FOR_REVIEWS,
            ['review_id' => $this->quote($review_ids, true), 'author' => $this->quote($author)],
            'review_id'
        );
    }

    public function getCommentsCount($reviewId)
    {
        return $this->getOne(self::QUERY_GET_COMMENTS_COUNT, ['review_id' => (int)$reviewId]);
    }

    public function getSnapshotListByReview($reviewId)
    {
        return $this->getAll(self::QUERY_GET_SNAPSHOTS_BY_REVIEW, ['review_id' => $reviewId]);
    }

    public function getSnapshotList($limit = 100, $max_id = 0)
    {
        $sql = str_replace('#LIST_PART#', $max_id ? self::PART_SNAPSHOTS_LIST : '', self::QUERY_GET_SNAPSHOTS);
        return $this->getAll($sql, ['limit' => (int)$limit, 'id' => (int)$max_id]);
    }

    public function getReview($ticket, $hash, $session_review_id = null)
    {
        $snapshot_list = [];
        if ($snapshots = $this->findSnapshotByTicket($ticket)) {
            foreach ($snapshots as $snapshot) {
                if (isset($snapshot_list[$snapshot['review_id']])) continue;
                $snapshot_list[$snapshot['review_id']] = $snapshot + ['origin' => 'ticket'];
            }
        }
        if ($snapshots = $this->findSnapshotByHash($hash)) {
            foreach ($snapshots as $snapshot) {
                if (isset($snapshot_list[$snapshot['review_id']])) continue;
                $snapshot_list[$snapshot['review_id']] = $snapshot + ['origin' => 'hash'];
            }
        }
        if ($session_review_id && $snapshots = $this->getSnapshotListByReview($session_review_id)) {
            foreach ($snapshots as $snapshot) {
                if (isset($snapshot_list[$snapshot['review_id']])) continue;
                $snapshot_list[$snapshot['review_id']] = $snapshot + ['origin' => 'session'];
            }
        }

        return $snapshot_list;
    }

    public function setReviewCommentsStatusByAuthor($reviewId, $author, $status, $newStatus)
    {
        $sql = str_replace('#PART_STATUS#', $status ? self::PART_REVIEW_STATUS : '', self::QUERY_SET_REVIEW_STATUS);
        $params = [
            'status' => $this->quote($status),
            'new_status' => $this->quote($newStatus),
            'author' => $this->quote($author),
            'review_id' => $this->quote($reviewId),
        ];
        return $this->query($sql, $params);
    }

    public function findComment($snapshotId, $author, $file, $line, $status, $side = '')
    {
        $side_condition = '';
        if ($side === null) $side_condition = self::PART_COMMENT_NOSIDE;
        else if ($side === true) $side_condition = self::PART_COMMENT_SIDE_SOME;
        else if (!empty($side) && in_array($side, ['lhs', 'rhs'])) $side_condition = self::PART_COMMENT_SIDE;
        $sql = str_replace('#PART_SIDE#', $side_condition, self::QUERY_FIND_COMMENT);
        $params = [
            'snapshot_id' => $this->quote($snapshotId),
            'author' => $this->quote($author),
            'file' => $this->quote($file),
            'line' => $this->quote($line),
            'status' => $this->quote($status),
            'side' => $this->quote($side),
        ];
        return $this->getAll($sql, $params);
    }

    public function saveBranchHead($branch, $hash)
    {
        return false !== $this->query(self::QUERY_SAVE_BRANCH_HEAD, ['branch' => $this->quote($branch), 'hash' => $this->quote($hash)]);
    }

    public function getBranchHead($branch)
    {
        return $this->getOne(self::QUERY_GET_BRANCH_HEAD, ['branch' => $this->quote($branch)]);
    }

    public function deleteAllDraftComments($author)
    {
        return $this->query(self::QUERY_DELETE_ALL_DRAFT_COMMENTS, ['author' => $this->quote($author)]);
    }

    protected function getOne($sql, $params = [])
    {
        $result = $this->query($sql, $params);
        if (!$result) return false;

        $row = $result->fetchRow();

        $result->freeResult();

        return $row && array_key_exists(0, $row) ? $row[0] : false;
    }

    public function getAll($sql, array $params = [])
    {
        $result = $this->query($sql, $params);
        if (!$result) return false;

        $rows = [];
        while ($row = $result->fetchAssoc()) {
            $rows[] = $row;
        }

        $result->freeResult();

        return $rows;
    }

    public function getAssoc($sql, $params = [], $field = '')
    {
        $result = $this->query($sql, $params);
        if (!$result) return false;

        $fields_num = $result->getFieldsNum();
        $field = $field ? $field : $result->getFieldName(0); // first field will be
        $second_field = $fields_num == 2 ? $result->getFieldName(1) : null;

        $rows = [];
        while ($row = $result->fetchAssoc()) {
            $rows[$row[$field]] = ($fields_num != 2) ? $row : $row[$second_field];
        }

        $result->freeResult();

        return $rows;
    }

    public function getRow($sql, $params = [])
    {
        $result = $this->query($sql, $params);
        if (!$result) return false;

        $row = $result->fetchAssoc();

        $result->freeResult();

        return $row;
    }

    protected function getTablesAsDbwrapper()
    {
        static $tables;

        if (!isset($tables)) {
            $tables = [];

            $refl = new ReflectionClass(get_class($this));

            $prefix = 'TBL_';

            foreach ($refl->getConstants() as $const => $value) {
                if (substr($const, 0, strlen($prefix)) != $prefix) continue;
                $tables[$const] = ($this->db ? $this->db . '.' : '') . $value;
            }
        }
        return $tables;
    }

}
