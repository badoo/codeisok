<?php

class DiffHighlighter
{
    const R_BEGIN_PLACEHOLDER = '%__RBEGIN__%';
    const R_END_PLACEHOLDER = '%__REND__%';
    const A_BEGIN_PLACEHOLDER = '%__ABEGIN__%';
    const A_END_PLACEHOLDER = '%__AEND__%';

    public static function mark($diff)
    {
        $lines = explode("\n", $diff);
        $DiffHighlighter = new DiffHighlighter();

        foreach ($lines as $i => $ln) {
            if (!isset($lines[$i + 1]) || !isset($lines[$i - 1])) continue;
            if (isset($ln[0]) && $ln[0] == '-' && mb_orig_strpos($ln, '---') !== 0 && (!mb_orig_strlen($lines[$i - 1]) || $lines[$i - 1][0] != '-')) {
                $next_line = $lines[$i + 1];
                if (!mb_orig_strlen($next_line) || $next_line[0] != '+'
                    || isset($lines[$i + 2]) && mb_orig_strlen($lines[$i + 2]) && $lines[$i + 2][0] == '+')continue;

                list($ln, $next_line) = $DiffHighlighter->get_with_markers($ln, $next_line);
                $lines[$i] = $ln;
                $lines[$i + 1] = $next_line;
            } else {
                continue;
            }
        }

        $diff = implode("\n", $lines);
        return $diff;
    }

    /**
     * @param $diff
     * @return array Marks in form [char offset, char length, 'removed' | 'added']
     */
    public static function getMarks($diff)
    {
        $lines = explode("\n", $diff);

        $DiffHighlighter = new DiffHighlighter();
        $result = [];
        $pos = mb_strlen($lines[0]) + 1 /* newline removed in explode */;
        for ($count = count($lines) - 1, $i = 1; $i < $count; $i++) {
            if (!isset($lines[$i][0]) || !isset($lines[$i + 1][0]) || $lines[$i][0] != '-' || $lines[$i + 1][0] != '+'
                || mb_orig_substr($lines[$i], 0, 3) == '---'
                || (isset($lines[$i + 2][0]) && $lines[$i + 2][0] == '+')
                || (isset($lines[$i - 1][0]) && $lines[$i - 1][0] == '-')) {
                $pos += mb_strlen($lines[$i]) + 1;
                continue;
            }

            $removed_array = preg_split('//u', $lines[$i], -1, PREG_SPLIT_NO_EMPTY);
            $added_array = preg_split('//u', $lines[$i + 1], -1, PREG_SPLIT_NO_EMPTY);
            array_shift($removed_array);
            array_shift($added_array);

            list ($chars_removed, $chars_added) = $DiffHighlighter->get_changed_lines(implode("\n", $removed_array), implode("\n", $added_array));
            foreach ($chars_removed as $char_removed) {
                $char_removed = explode(',', $char_removed);
                if (isset($char_removed[1])) $result[] = [$pos + $char_removed[0], $char_removed[1] - $char_removed[0] + 1, 'removed'];
                else $result[] = [$pos + $char_removed[0], 1, 'removed'];
            }
            $pos += count($removed_array) + 2 /* newline in explode and '-' shifted */;
            foreach ($chars_added as $char_added) {
                $char_added = explode(',', $char_added);
                if (isset($char_added[1])) $result[] = [$pos + $char_added[0], $char_added[1] - $char_added[0] + 1, 'added'];
                else $result[] = [$pos + $char_added[0], 1, 'added'];
            }
            $i++;
            $pos += count($added_array) + 2;
        }
        return $result;
    }

    protected function get_with_markers($removed, $added)
    {
        $removed_array = preg_split('//u', $removed, -1, PREG_SPLIT_NO_EMPTY);
        $added_array = preg_split('//u', $added, -1, PREG_SPLIT_NO_EMPTY);
        array_shift($removed_array);
        array_shift($added_array);
        list ($res_old, $res_new) = $this->get_changed_lines(implode("\n", $removed_array), implode("\n", $added_array));
        foreach ($res_old as $key) {
            $range = explode(',', $key);
            if (isset($range[1])) {
                $removed_array[$range[0] - 1] = self::R_BEGIN_PLACEHOLDER . $removed_array[$range[0] - 1];
                $removed_array[$range[1] - 1] = $removed_array[$range[1] - 1] . self::R_END_PLACEHOLDER;
            } else {
                $removed_array[$key - 1] = self::R_BEGIN_PLACEHOLDER . $removed_array[$key - 1] . self::R_END_PLACEHOLDER;
            }
        }
        foreach ($res_new as $key) {
            $range = explode(',', $key);
            if (isset($range[1])) {
                $added_array[$range[0] - 1] = self::A_BEGIN_PLACEHOLDER . $added_array[$range[0] - 1];
                $added_array[$range[1] - 1] = $added_array[$range[1] - 1] . self::A_END_PLACEHOLDER;
            } else {
                $added_array[$key - 1] = self::A_BEGIN_PLACEHOLDER . $added_array[$key - 1] . self::A_END_PLACEHOLDER;
            }
        }
        array_unshift($removed_array, '-');
        array_unshift($added_array, '+');
        return array(implode("", $removed_array), implode("", $added_array));
    }

    /**
     * @param $old_content string with one character per line
     * @param $new_content string Same as old
     * @return array Pair (old, new) of list of line numbers or comma-separated line ranges (57,58)
     */
    protected function get_changed_lines($old_content, $new_content)
    {
        $tmpfile_old = $tmpfile_new = null;
        try {
            $tmpfile_old = tempnam("/tmp", "gitphp_apply");
            $tmpfile_new = tempnam("/tmp", "gitphp_apply");
            if (!$tmpfile_old || !$tmpfile_new) throw new Exception;
            if (file_put_contents($tmpfile_old, $old_content) === false) throw new Exception;
            if (file_put_contents($tmpfile_new, $new_content) === false) throw new Exception;
            exec("diff " . escapeshellarg($tmpfile_old) . " " . escapeshellarg($tmpfile_new), $out, $retval);
            if ($retval > 1) throw new Exception;
        } catch (\Exception $e) {
            return [[], []];
        } finally {
            if ($tmpfile_old) unlink($tmpfile_old);
            if ($tmpfile_new) unlink($tmpfile_new);
        }

        $lines_old = $lines_new = [];
        foreach ($out as $row) {
            if (!preg_match('#^([0-9,]+)([acd])([0-9,]+)$#', $row, $m)) continue;
            if (strpos($m[1], ',') !== false || $m[2] !== 'a') $lines_old[$m[1]] = true;
            if (strpos($m[3], ',') !== false || $m[2] !== 'd') $lines_new[$m[3]] = true;
        }
        $lines_old = array_keys($lines_old);
        sort($lines_old);
        $lines_new = array_keys($lines_new);
        sort($lines_new);
        return [$lines_old, $lines_new];
    }
}
