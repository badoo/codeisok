<?php
class GitPHP_Util
{
    const DEBUG_EMAIL = '';

    public static $diff_size = 8;

    /**
     * AddSlash
     *
     * Adds a trailing slash to a directory path if necessary
     *
     * @access public
     * @static
     * @param string $path path to add slash to
     * @param bool $backslash true to also check for backslash (windows paths)
     * @return string $path with a trailing slash
     */
    public static function AddSlash($path, $backslash = true)
    {
        if (empty($path)) return $path;

        $end = substr($path, -1);

        if (!((($end == '/') || ($end == ':')) || ($backslash && (strtoupper(substr(PHP_OS, 0, 3))) && ($end == '\\')))) $path .= '/';

        return $path;
    }

    public static function sendReviewEmail($from, $review_name, $url, $comments, $review_type = 'unified')
    {
        $headers = "From:" . $from . "\nContent-Type: text/html; charset=utf-8";
        $to = [$from];

        $tracker_link = '';
        $ticket_key = \GitPHP\Tracker::instance()->parseTicketFromString($review_name);
        if (!empty($ticket_key)) {
            $ticket_summary = \GitPHP\Tracker::instance()->getTicketSummary($ticket_key);
            $review_name .= ' - ' . $ticket_summary;
            $tracker_link = "(<a href=\"" . \GitPHP\Tracker::instance()->getTicketUrl($ticket_key) . "\">"
                . htmlspecialchars($review_name) . "</a>)";
            $developer_email = \GitPHP\Tracker::instance()->getTicketDeveloperEmail($ticket_key);
            if (!empty($developer_email)) {
                $to[] = $developer_email;
            }
        }

        $first = reset($comments);
        $Project = GitPHP_ProjectList::GetInstance()->GetProject($first['repo']);
        $project_notify_email = $Project->GetNotifyEmail();
        if (!empty($project_notify_email)) {
            $to[] = $project_notify_email;
        }

        $comments_authors = $changes_authors = [];
        $diff = self::insertCommentsToDiffs($comments, 'html', $comments_authors, $changes_authors, $review_type);

        if (\GitPHP\Tracker::instance()->enabled()) {
            $comments_authors = array_unique($comments_authors);
            foreach ($comments_authors as $comment_author) {
                $author_email = \GitPHP\Tracker::instance()->getUserEmail($comment_author);
                if (!empty($author_email)) {
                    $to[] = $author_email;
                }
            }
        }
        $to = array_unique($to);
        $ignored_emails = \GitPHP_Config::GetInstance()->GetValue(\GitPHP_Config::IGNORED_EMAIL_ADDRESSES, []);
        $to = array_filter($to, function ($address) use ($ignored_emails) { return !in_array($address, $ignored_emails); });

        $subject = "[GITPHP] ($review_name) Comment from $from";

        if ($changes_authors) {
            $title = 'Changes from the following authors has been reviewed';
            $undertitle = '<ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $changes_authors)) . '</li></ul>';
        } else {
            $title = 'Your changes has been reviewed';
            $undertitle = '';
        }
        $message = "<h2>$title $tracker_link:</h2>$undertitle<br>$url<br><div style=\"line-height:1.5em;font: 12px monospace; margin-bottom: 20px;\">$diff</div>";

        $to = implode(',', $to);

        $add_params = "-f $from";
        if (self::DEBUG_EMAIL) {
            $message .= "<pre>ORIG_TO:$to\nORIG_ADDPARAMS:$add_params\nORIG_HEADERS:$headers\n</pre>";
            $headers = 'Content-Type: text/html; charset=utf-8';
            $to = self::DEBUG_EMAIL;
            $add_params = null;
        }

        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        if (!mail($to, $subject, $message, $headers, $add_params)) {
            trigger_error("Mail to $to is not accepted for delivery");
        }
    }

    public static function addReviewToTracker($author_id, $review_name, $url, $comments, $review_type = 'unified')
    {
        $ticket_key = \GitPHP\Tracker::instance()->parseTicketFromString($review_name);
        if (!empty($ticket_key)) {
            $comments_authors = $changes_authors = [];
            $format = \GitPHP\Tracker::instance()->getCommentsFormat();
            $diff = self::insertCommentsToDiffs($comments, $format, $comments_authors, $changes_authors, $review_type);
            $comment = "";
            if (\GitPHP\Tracker::instance()->getTrackerType() == \GitPHP\Tracker::TRACKER_TYPE_JIRA) {
                $diff = htmlspecialchars_decode(strip_tags($diff));
                $comment = "Review from $author_id: \n$url\n" . str_replace('{code}{code}', '', "{code}$diff{code}");
                $comment = str_replace("{quote}{code}\n{code}{quote}", '{quote}{quote} ⤷ ', $comment);
            } elseif (\GitPHP\Tracker::instance()->getTrackerType() == \GitPHP\Tracker::TRACKER_TYPE_REDMINE) {
                $diff = htmlspecialchars_decode($diff);
                $comment = "Review from *$author_id*: \n$url\n" . str_replace("<pre>\n</pre>\n", "", "<pre>$diff</pre>");
                $comment = str_replace("_\n>*", "_\n>⤷ *", $comment);
            }
            if (self::DEBUG_EMAIL) {
                mail(self::DEBUG_EMAIL, "Post to tracker ticket $review_name", $comment);
                return;
            }
            try {
                \GitPHP\Tracker::instance()->addComment($ticket_key, $comment);
            } catch (\Exception $e) {
                trigger_error($e);
            }
        }
    }

    public static function insertCommentsToDiffs($comments, $format, &$comments_authors, &$changes_authors, $review_type = 'unified')
    {
        if (!is_array($changes_authors)) $changes_authors = [];
        $first = reset($comments);
        $Project = GitPHP_ProjectList::GetInstance()->GetProject($first['repo']);

        /* собираем структурку hash: file: comment */
        $hash_files_comments = [];
        $blob_hashes = [];
        foreach ($comments as $comment) {
            $comments_authors[] = $comment['author'];
            if ($review_type == 'sidebyside') {
                // нужно чтобы один код генерил диффы для обоих ситуаций
                $comment['real_line'] = $comment['line'] + 1;
                $comment['line'] = null;
                $comment['real_line_before'] = null;
                if (!isset($blob_hashes[$comment['file']][$comment['side']])) {
                    $commit_hash = $comment['side'] == 'lhs' ? $comment['hash_base'] : $comment['hash_head'];
                    $blob_hashes[$comment['file']][$comment['side']] = GitPHP_Blob::getBlobHash($Project, $commit_hash, $comment['file']);
                }
                // маппинг будет вида [hash-blob => [file => [id => text]]]
                // в итоге у нас для каждой стороны будет генериться дифф как для блоба
                $hash_files_comments[$blob_hashes[$comment['file']][$comment['side']] . '-' . 'blob'][$comment['file']][$comment['id']] = $comment;
            } else {
                if (!isset($hash_files_comments[$comment['hash_head'] . '-' . $comment['hash_base']][$comment['file']])) {
                    $hash_files_comments[$comment['hash_head'] . '-' . $comment['hash_base']][$comment['file']] = [];
                }
                $hash_files_comments[$comment['hash_head'] . '-' . $comment['hash_base']][$comment['file']][$comment['id']] = $comment;
            }
        }

        $DiffContext = new DiffContext();
        $DiffContext->setRenames(true);
        $vars_data = ['DIFF_OBJS' => []];
        /* разбираем */
        foreach ($hash_files_comments as $hash => $files) {
            foreach ($files as $file => $comments) {
                $Diff = self::getDiffCached($hash, $DiffContext, $Project, $changes_authors);
                $vars_data['DIFF_OBJS'][] = self::insertCommentsToDiffObj($comments, $file, $Diff);
            }
        }
        $template = 'review.mail.tpl';
        if ($format == 'jira') {
            $template = 'review.jira.tpl';
        } elseif ($format == 'redmine') {
            $template = 'review.redmine.tpl';
        }
        $View = new Smarty;
        $View->plugins_dir[] = GITPHP_INCLUDEDIR . 'smartyplugins';
        $View->template_dir = GITPHP_TEMPLATESDIR;

        $View->assign($vars_data);
        $diff = $View->fetch($template);

        $changes_authors = array_unique($changes_authors);
        foreach ($changes_authors as $idx => $author) {
            foreach (GitPHP_Config::GetInstance()->GetValue(\GitPHP_Config::COLLECT_CHANGES_AUTHORS_SKIP, []) as $skip_author) {
                if (strpos($author, $skip_author) !== false) unset($changes_authors[$idx]);
            }
        }
        return $diff;
    }

    protected static function getDiffCached($hash, DiffContext $DiffContext, GitPHP_Project $Project, &$changes_authors)
    {
        static $diffs = [];

        if (!isset($diffs[$hash])) {
            list($hash_head, $hash_base) = explode('-', $hash);
            if (empty($hash_base)) {
                $diffs[$hash] = new GitPHP_TreeDiff($Project, $hash_head, '', $DiffContext);
                if (GitPHP_Config::GetInstance()->GetValue(\GitPHP_Config::COLLECT_CHANGES_AUTHORS, false)) {
                    $changes_authors[] = $Project->GetCommit($hash_head)->GetAuthor();
                }
            } else if ($hash_base == 'blob') {
                $diffs[$hash] = new GitPHP_Blob($Project, $hash_head);
            } else {
                $diffs[$hash] = new GitPHP_BranchDiff($Project, $hash_head, $hash_base, $DiffContext);

                if (GitPHP_Config::GetInstance()->GetValue(\GitPHP_Config::COLLECT_CHANGES_AUTHORS, false)) {
                    $log = $Project->GetLog($hash_head, 50, 0, $hash_base);
                    if (is_array($log)) {
                        foreach ($log as $commit) {
                            /** @var $commit GitPHP_Commit */
                            $changes_authors[] = $commit->GetAuthor();
                        }
                    }
                }
            }
        }
        return $diffs[$hash];
    }

    /**
     * @static
     * @param array[] $comments
     * @param string $file
     * @param GitPHP_BranchDiff|GitPHP_TreeDiff|GitPHP_FileDiff[]|GitPHP_Blob $Diffs
     * @param int $diff_size
     * @return string
     */
    public static function insertCommentsToDiffObj($comments, $file, $Diffs, $diff_size = 4)
    {
        $result = [];
        $startLine = 0;
        if ($Diffs instanceof GitPHP_Blob) {
            $lines = $Diffs->GetData(true);
            $result['HEADER'] = [['header' => $file]];
        } else {
            $Diff = null;
            foreach ($Diffs as $FileDiff) {
                if ($FileDiff->GetFromFile() == $file || $FileDiff->GetToFile() == $file) {
                    $Diff = $FileDiff;
                    break;
                }
            }

            if (!$Diff) {
                return 'File not found in diffs: ' . $file;
            }
            $lines = $Diff->GetDiff($file, true, true, false);
            $startLine = 2;
            $result['HEADER'] = [
                ['header' => $lines[0]],
                ['header' => $lines[1]],
            ];
        }

        /* собираем карту номер строки до/после в номер абсолютной строки в текущем размере контекста */
        /* строки не только diff, но и blob - первый символ может отсутствовать - поэтому isset($lines[$i][0]) */
        $linesMapBefore = $linesMapAfter = $linesMapAbs = [];
        for ($i = $startLine, $linesCount = count($lines), $line_before = $line_after = 0; $i < $linesCount; $i++) {
            if (preg_match('#^@@ -(\d+),\d+ \+(\d+),\d+ @@#', $lines[$i], $m)) {
                $line_before = $m[1] - 1;
                $line_after = $m[2] - 1;
            } else if (isset($lines[$i][0]) && $lines[$i][0] == '-') {
                $line_before++;
                $linesMapBefore[$line_before] = $i;
            } else if (isset($lines[$i][0]) && $lines[$i][0] == '+') {
                $line_after++;
                $linesMapAfter[$line_after] = $i;
            } else {
                $line_before++;
                $line_after++;
                $linesMapBefore[$line_before] = $i;
                $linesMapAfter[$line_after] = $i;
            }
            $linesMapAbs[$i] = [$line_before, $line_after];
        }

        /* проверим попадает ли строка кода в контекст коммента */
        $contextLines = $commentLines = $no_line_comments = [];
        foreach ($comments as $commentId => $comment) {
            $absLine = -1;
            if (!empty($comment['real_line']) && isset($linesMapAfter[$comment['real_line']])) $absLine = $linesMapAfter[$comment['real_line']];
            if (!empty($comment['real_line_before']) && isset($linesMapBefore[$comment['real_line_before']])) $absLine = $linesMapBefore[$comment['real_line_before']];
            if ($absLine == -1) {
                $no_line_comments[] = $comment;
                continue;
            }
            for ($i = $absLine - $comment['lines_count'] - $diff_size; $i < $absLine + $diff_size; $i++) {
                /* line is out of bound due to context size, or map started from index 2 */
                if (!isset($lines[$i]) || !isset($linesMapAbs[$i])) continue;
                $contextLines[$i] = true;
            }
            $commentLines[$absLine][] = $commentId;
        }
        $contextLines = array_keys($contextLines);
        sort($contextLines);

        /* вывести комменты без строчки сразу */
        $result['TOP_COMMENT'] = [];
        foreach ($no_line_comments as $comment) {
            $result['TOP_COMMENT'][] = [
                'date' => self::formatDate(strtotime($comment['date'])),
                'author' => $comment['author'],
                'comment' => $comment['text'] /* already escaped, see \GitPHP_Db::addComment */,
            ];
        }

        /* вывести строки попавшие в контекст комментов */
        $prevLine = 0;
        $result['LINE'] = [];
        foreach ($contextLines as $i) {
            $color = 'gray';
            switch (substr($lines[$i], 0, 1)) {
                case '+':
                    $color = 'green';
                    $line_numbers = sprintf('%4s%4s ', '', $linesMapAbs[$i][1]);
                    break;

                case '-':
                    $color = 'red';
                    $line_numbers = sprintf('%4s%4s ', $linesMapAbs[$i][0], '');
                    break;

                case '@':
                    $line_numbers = sprintf('%4s%4s ', '', '');
                    break;

                default:
                    $line_numbers = sprintf('%4s%4s ', $linesMapAbs[$i][0], $linesMapAbs[$i][1]);
            }

            $current_line = [
                'color' => $color,
                'line_numbers' => $line_numbers,
                'line' => htmlspecialchars($lines[$i]),
                'COMMENT' => [],
            ];
            if ($prevLine && $i != $prevLine + 1) {
                $current_line['SEPARATOR'] = [[]];
            }

            /* вывести все комменты к этой строке */
            if (isset($commentLines[$i])) {
                foreach ($commentLines[$i] as $commentId) {
                    $current_line['COMMENT'][] = [
                        'date' => self::formatDate(strtotime($comments[$commentId]['date'])),
                        'author' => $comments[$commentId]['author']/* already escaped, see \GitPHP_Db::addComment */,
                        'comment' => $comments[$commentId]['text']/* already escaped, see \GitPHP_Db::addComment */,
                    ];
                }
            }
            $prevLine = $i;
            $result['LINE'][] = $current_line;
        }
        return $result;
    }

    protected static function formatDate($date)
    {
        $format = date('Y') == date('Y', $date) ? 'j M G:i' : 'j M Y G:i';
        return date($format, $date);
    }

    public static function getHostnameUrl()
    {
        return 'http://' . $_SERVER['HTTP_HOST'];
    }

    public static function getReviewLink($snapshot, $file)
    {
        $params = [
            'p' => $snapshot['repo'],
            'o' => $snapshot['review_type'],
            'review' => $snapshot['review_id'],
        ];
        if ($snapshot['hash_base'] == 'blob') {
            $params['h'] = $snapshot['hash_head'];
            $params['f'] = $file;
            $url = GitPHP_Application::getUrl('blob', $params);
        } else if ($snapshot['hash_base']) {
            $params['branch'] = $snapshot['hash_head'];
            $params['base'] = $snapshot['hash_base'];
            $url = GitPHP_Application::getUrl('branchdiff', $params);
        } else {
            $params['h'] = $snapshot['hash_head'];
            $url = GitPHP_Application::getUrl('commitdiff', $params);
        }
        return $url;
    }
    
    public static function humanFilesize($size, $precision = 2)
    {
        for($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {}
        return round($size, $precision).' '.['B','kB','MB','GB','TB','PB','EB','ZB','YB'][$i];
    }
}
