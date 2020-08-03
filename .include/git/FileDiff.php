<?php

namespace GitPHP\Git;

class FileDiff
{
    const LARGE_DIFF_SIZE = 10000;

    /**
     * diffInfoRead
     *
     * Stores whether diff info has been read
     *
     * @access protected
     */
    protected $diffInfoRead = false;

    /**
     * diffDataRead
     *
     * Stores whether diff data has been read
     *
     * @access protected
     */
    protected $diffDataRead = false;

    /**
     * diffData
     *
     * Stores the diff data
     *
     * @access protected
     */
    protected $diffData;

    /**
     * diffDataSplitRead
     *
     * Stores whether split diff data has been read
     *
     * @access protected
     */
    protected $diffDataSplitRead = false;

    /**
     * diffDataSplit
     *
     * Stores the diff data split up by left/right changes
     *
     * @access protected
     */
    protected $diffDataSplit;

    /**
     * diffDataName
     *
     * Filename used on last data diff
     *
     * @access protected
     */
    protected $diffDataName;

    /**
     * fromMode
     *
     * Stores the from file mode
     *
     * @access protected
     */
    protected $fromMode;

    /**
     * toMode
     *
     * Stores the to file mode
     *
     * @access protected
     */
    protected $toMode;

    /**
     * fromHash
     *
     * Stores the from hash
     *
     * @access protected
     */
    protected $fromHash;

    /**
     * toHash
     *
     * Stores the to hash
     *
     * @access protected
     */
    protected $toHash;
    protected $toHashOriginal;

    /**
     * status
     *
     * Stores the status
     *
     * @access protected
     */
    protected $status;

    /**
     * similarity
     *
     * Stores the similarity
     *
     * @access protected
     */
    protected $similarity;

    /**
     * fromFile
     *
     * Stores the from filename
     *
     * @access protected
     */
    protected $fromFile;

    /**
     * toFile
     *
     * Stores the to filename
     *
     * @access protected
     */
    protected $toFile;

    /**
     * fromFileType
     *
     * Stores the from file type
     *
     * @access protected
     */
    protected $fromFileType;

    /**
     * toFileType
     *
     * Stores the to file type
     *
     * @access protected
     */
    protected $toFileType;

    /**
     * project
     *
     * Stores the project
     *
     * @access protected
     */
    protected $project;

    /**
     * commit
     *
     * Stores the commit that caused this filediff
     *
     * @access protected
     */
    protected $commit;

    protected $diffTooLarge = false;

    protected $diffTreeLine = null;

    /**
     * @var bool
     */
    protected $branch;

    /**
     * @var \GitPHP\Git\DiffContext
     */
    protected $DiffContext;

    protected $decorationData;

    protected $inline_changes;

    private $extension;
    private $root_folder;

    protected $diffTypeImage = false;

    /**
     * @param mixed $project project
     * @param string $fromHash source hash, can also be a diff-tree info line
     * @param string $toHash target hash, required if $fromHash is a hash
     * @param \GitPHP\Git\DiffContext $DiffContext
     * @param string $branch
     * @throws \Exception
     */
    public function __construct(\GitPHP\Git\Project $project, $fromHash, $toHash = '', \GitPHP\Git\DiffContext $DiffContext, $branch = '')
    {
        $this->project = $project;
        $this->toHashOriginal = $toHash;
        $this->branch = $branch;
        $this->DiffContext = $DiffContext;

        if (!$this->ParseDiffTreeLine($fromHash)) {
            if (!(preg_match('/^[0-9a-fA-F]{40}$/', $fromHash) && preg_match('/^[0-9a-fA-F]{40}$/', $toHash))) {
                throw new \Exception('Invalid parameters for FileDiff');
            }

            $this->fromHash = $fromHash;
            $this->toHash = $toHash;
        }
    }

    /**
     * ParseDiffTreeLine
     *
     * @param string $diffTreeLine line from difftree
     * @return bool
     * @throws \Exception
     */
    private function ParseDiffTreeLine($diffTreeLine)
    {
        if (preg_match('/^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)([0-9]{0,3})\t(.*)$/', $diffTreeLine, $regs)) {
            $this->diffInfoRead = true;

            $this->diffTreeLine = $diffTreeLine;

            $this->fromMode = $regs[1];
            $this->toMode = $regs[2];
            $this->fromHash = $regs[3];
            $this->toHash = $regs[4];
            $this->status = $regs[5];
            $this->similarity = ltrim($regs[6], '0');
            $this->fromFile = strtok($regs[7], "\t");
            $this->toFile = strtok("\t");
            if ($this->toFile === false) {
                /* no filename change */
                $this->toFile = $this->fromFile;
            }

            return true;
        }

        return false;
    }

    /**
     * ReadDiffInfo
     *
     * Reads file diff info
     *
     * @access protected
     */
    protected function ReadDiffInfo()
    {
        $this->diffInfoRead = true;

        /* TODO: read a single difftree line on-demand */
    }

    /**
     * GetFromMode
     *
     * Gets the from file mode
     * (full a/u/g/o)
     *
     * @access public
     * @return string from file mode
     */
    public function GetFromMode()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return $this->fromMode;
    }

    /**
     * GetFromModeShort
     *
     * Gets the from file mode in short form
     * (standard u/g/o)
     *
     * @access public
     * @return string short from file mode
     */
    public function GetFromModeShort()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return substr($this->fromMode, -4);
    }

    /**
     * GetToMode
     *
     * Gets the to file mode
     * (full a/u/g/o)
     *
     * @access public
     * @return string to file mode
     */
    public function GetToMode()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return $this->toMode;
    }

    /**
     * GetToModeShort
     *
     * Gets the to file mode in short form
     * (standard u/g/o)
     *
     * @access public
     * @return string short to file mode
     */
    public function GetToModeShort()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return substr($this->toMode, -4);
    }

    /**
     * GetFromHash
     *
     * Gets the from hash
     *
     * @access public
     * @return string from hash
     */
    public function GetFromHash()
    {
        return $this->fromHash;
    }

    /**
     * GetToHash
     *
     * Gets the to hash
     *
     * @access public
     * @return string to hash
     */
    public function GetToHash()
    {
        return $this->toHash;
    }

    /**
     * GetFromBlob
     *
     * Gets the from file blob
     *
     * @access public
     * @return mixed blob object
     */
    public function GetFromBlob()
    {
        if (empty($this->fromHash)) return null;

        return $this->project->GetBlob($this->fromHash);
    }

    /**
     * GetToBlob
     *
     * Gets the to file blob
     *
     * @access public
     * @return \GitPHP\Git\Blob blob object
     */
    public function GetToBlob()
    {
        if (empty($this->toHash)) return null;

        return $this->project->GetBlob($this->toHash);
    }

    /**
     * GetStatus
     *
     * Gets the status of the change
     *
     * @access public
     * @return string status
     */
    public function GetStatus()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return $this->status;
    }

    /**
     * GetSimilarity
     *
     * Gets the similarity
     *
     * @access public
     * @return string similarity
     */
    public function GetSimilarity()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return $this->similarity;
    }

    /**
     * GetFromFile
     *
     * Gets the from file name
     *
     * @access public
     * @return string from file
     */
    public function GetFromFile()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return $this->fromFile;
    }

    /**
     * GetToFile
     *
     * Gets the to file name
     *
     * @access public
     * @return string to file
     */
    public function GetToFile()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return $this->toFile;
    }

    public function getToFileExtension()
    {
        if (is_null($this->extension)) {
            $filename_arr = explode('.', basename($this->GetToFile()));
            $this->extension = $filename_arr[count($filename_arr) - 1];
        }
        return $this->extension;
    }

    public function getToFileRootFolder()
    {
        $skip_parent_folder = '_packages';

        if (is_null($this->root_folder)) {
            $filename_arr = explode('/', $this->GetToFile());
            $this->root_folder = ($filename_arr[0] == $skip_parent_folder && isset($filename_arr[1])) ? $filename_arr[1] : $filename_arr[0];
        }
        return $this->root_folder;
    }

    /**
     * GetFromFileType
     *
     * Gets the from file type
     *
     * @access public
     * @param boolean $local true if caller wants localized type
     * @return string from file type
     */
    public function GetFromFileType($local = false)
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return \GitPHP\Git\Blob::FileType($this->fromMode, $local);
    }

    /**
     * GetToFileType
     *
     * Gets the to file type
     *
     * @access public
     * @param boolean $local true if caller wants localized type
     * @return string to file type
     */
    public function GetToFileType($local = false)
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return \GitPHP\Git\Blob::FileType($this->toMode, $local);
    }

    /**
     * FileTypeChanged
     *
     * Tests if filetype changed
     *
     * @access public
     * @return boolean true if file type changed
     */
    public function FileTypeChanged()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return (octdec($this->fromMode) & 0x17000) != (octdec($this->toMode) & 0x17000);
    }

    /**
     * FileModeChanged
     *
     * Tests if file mode changed
     *
     * @access public
     * @return boolean true if file mode changed
     */
    public function FileModeChanged()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return (octdec($this->fromMode) & 0777) != (octdec($this->toMode) & 0777);
    }

    /**
     * FromFileIsRegular
     *
     * Tests if the from file is a regular file
     *
     * @access public
     * @return boolean true if from file is regular
     */
    public function FromFileIsRegular()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return (octdec($this->fromMode) & 0x8000) == 0x8000;
    }

    /**
     * ToFileIsRegular
     *
     * Tests if the to file is a regular file
     *
     * @access public
     * @return boolean true if to file is regular
     */
    public function ToFileIsRegular()
    {
        if (!$this->diffInfoRead) $this->ReadDiffInfo();

        return (octdec($this->toMode) & 0x8000) == 0x8000;
    }

    public function GetLargeDiffSize()
    {
        return \GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::LARGE_DIFF_SIZE, self::LARGE_DIFF_SIZE);
    }

    /**
     * GetDiff
     *
     * Gets the diff output
     *
     * @param string $file override the filename on the diff
     * @param bool $readFileData
     * @param bool $explode
     * @param bool $highlight_changes
     * @return string diff output
     */
    public function GetDiff($file = '', $readFileData = true, $explode = false, $highlight_changes = true)
    {
        if ($this->diffDataRead && ($file == $this->diffDataName) || $this->getDiffTooLarge() && $readFileData) {
            if ($explode) return explode("\n", $this->diffData);
            else return $this->diffData;
        }

        if ((!$this->diffInfoRead) && $readFileData) $this->ReadDiffInfo();

        $this->diffDataName = $file;
        $this->diffDataRead = true;

        if (!empty($this->status) && !in_array($this->status, array('A', 'D', 'M', 'R'))) {
            trigger_error('Unknown status: ' . $this->status);
            $this->diffData = '';
            return null;
        }

        if (!$this->DiffContext->getIgnoreFormatting() && !empty($this->branch)) {
            $args = array();
            if (is_numeric($this->DiffContext->getContext())) {
                $args[] = '-U' . $this->DiffContext->getContext();
            } else {
                $args[] = '-u';
            }
            if ($this->DiffContext->getIgnoreWhitespace()) {
                $args[] = '-w';
            }
            if ($this->DiffContext->getRenames()) {
                $args[] = '-M';
            }
            $args = array_merge(
                $args,
                array(
                    $this->toHashOriginal,
                    $this->branch,
                    '--',
                    escapeshellarg($this->toFile)
                )
            );
            if ($this->DiffContext->getRenames()) {
                $args[] = escapeshellarg($this->fromFile);
            }
            $Git = new \GitPHP\Git\GitExe($this->project);
            $diff = trim($Git->Execute(GIT_DIFF, $args));
            $this->diffData = substr($diff, strpos($diff, '---'));
        } else if (!$this->DiffContext->getIgnoreFormatting() && !empty($this->toHashOriginal)) {
            $args = array();
            if (is_numeric($this->DiffContext->getContext())) {
                $args[] = '-U' . $this->DiffContext->getContext();
            } else {
                $args[] = '-u';
            }
            if ($this->DiffContext->getIgnoreWhitespace()) {
                $args[] = '-w';
            }
            if ($this->DiffContext->getRenames()) {
                $args[] = '-M';
            }
            $args = array_merge(
                $args,
                array(
                    '--pretty=format:',
                    $this->toHashOriginal,
                    '--',
                    escapeshellarg($this->toFile)
                )
            );
            if ($this->DiffContext->getRenames()) {
                $args[] = escapeshellarg($this->fromFile);
            }
            $Git = new \GitPHP\Git\GitExe($this->project);
            $diff = trim($Git->Execute(GIT_SHOW, $args));
            $this->diffData = substr($diff, strpos($diff, '---'));
        } else {
            $tmpdir = \GitPHP\Git\TmpDir::GetInstance();
            $pid = function_exists('posix_getpid') ? posix_getpid() : rand();

            $fromTmpFile = null;
            $toTmpFile = null;

            $fromName = null;
            $toName = null;

            if ((empty($this->status)) || ($this->status == 'D') || ($this->status == 'M') || ($this->status == 'R')) {
                $fromBlob = $this->GetFromBlob();
                $fromTmpFile = 'gitphp_' . $pid . '_from';
                $tmpdir->AddFile($fromTmpFile, $fromBlob->GetData());

                $fromName = 'a/';
                if (!empty($file)) {
                    $fromName .= $file;
                } else if (!empty($this->fromFile)) {
                    $fromName .= $this->fromFile;
                } else {
                    $fromName .= $this->fromHash;
                }
            }

            if ((empty($this->status)) || ($this->status == 'A') || ($this->status == 'M') || ($this->status == 'R')) {
                $toBlob = $this->GetToBlob();
                $toTmpFile = 'gitphp_' . $pid . '_to';
                $tmpdir->AddFile($toTmpFile, $toBlob->GetData());

                $toName = 'b/';
                if (!empty($file)) {
                    $toName .= $file;
                } else if (!empty($this->toFile)) {
                    $toName .= $this->toFile;
                } else {
                    $toName .= $this->toHash;
                }
            }

            $this->diffData = \GitPHP\Git\DiffExe::Diff(
                (empty($fromTmpFile) ? null : ($tmpdir->GetDir() . $fromTmpFile)),
                $fromName,
                (empty($toTmpFile) ? null : ($tmpdir->GetDir() . $toTmpFile)),
                $toName,
                $this->DiffContext->getContext(),
                $this->DiffContext->getIgnoreFormatting() ? false : $this->DiffContext->getIgnoreWhitespace()
            );

            if ($this->DiffContext->getIgnoreFormatting() && !empty($fromTmpFile)) {
                $this->diffWithFormattedBase($tmpdir, $fromTmpFile, $toTmpFile, $fromName, $toName);
            }
            if (!empty($fromTmpFile)) $tmpdir->RemoveFile($fromTmpFile);
            if (!empty($toTmpFile)) $tmpdir->RemoveFile($toTmpFile);
        }

        $fromBlob = $this->GetFromBlob();
        $toBlob = $this->GetToBlob();

        if (\GitPHP\Util::checkFileIsImage($this->fromFile) || \GitPHP\Util::checkFileIsImage($this->toFile)) {
            $this->diffData = \GitPHP\Util::getImagesDiff($fromBlob, $toBlob, $this->GetFromFile(), $this->GetToFile());
            $this->diffTypeImage = 1;
            return $this->diffData;
        }

        if (!$this->DiffContext->getSkipSuppress()) $this->diffTooLarge = mb_strlen($this->diffData) > $this->GetLargeDiffSize();

        if ($highlight_changes && mb_strlen($this->diffData) <= $this->GetLargeDiffSize()) {
            $this->inline_changes = \GitPHP\Git\DiffHighlighter::getMarks($this->diffData);
        }

        if ($highlight_changes) {
            $this->diffData = htmlspecialchars($this->diffData);
        }

        return $explode ? explode("\n", $this->diffData) : $this->diffData;
    }

    /**
     * GetDiffSplit
     *
     * construct the side by side diff data from the git data
     * The result is an array of ternary arrays with 3 elements each:
     * First the mode ("" or "-added" or "-deleted" or "-modified"),
     * then the first column, then the second.
     *
     * @author Mattias Ulbrich
     *
     * @access public
     * @return array[] of line elements (see above)
     */
    public function GetDiffSplit()
    {
        if ($this->diffDataSplitRead) {
            return $this->diffDataSplit;
        }

        $this->diffDataSplitRead = true;

        $exe = new \GitPHP\Git\GitExe($this->project);

        $args = array();
        if (is_numeric($this->DiffContext->getContext())) {
            $args[] = '-U' . $this->DiffContext->getContext();
        } else {
            $args[] = '-u';
        }
        $args[] = $this->fromHash;
        $args[] = $this->toHash;

        $diffLines = explode("\n", $exe->Execute(GIT_DIFF, $args));

        unset($exe);

        /* parse diffs */
        $diffs = array();
        $currentDiff = false;
        $state = 'initial';
        foreach ($diffLines as $d) {
            if (strlen($d) == 0) continue;
            switch ($d[0]) {
                case '@':
                    $state = 'marker';
                    if ($currentDiff) $diffs[] = $currentDiff;
                    $currentDiff = array("left" => array(), "right" => array(), "prev_context" => array($d), "post_context" => array());
                    break;

                case '+':
                    if ($state == 'post') {
                        if ($currentDiff) $diffs[] = $currentDiff;
                        $currentDiff = array("left" => array(), "right" => array(), "prev_context" => array(), "post_context" => array());
                        $state = 'diff';
                    } else if ($state == 'marker') $state = 'diff';
                    if ($currentDiff) $currentDiff["right"][] = substr($d, 1);
                    break;

                case '-':
                    if ($state == 'post') {
                        if ($currentDiff) $diffs[] = $currentDiff;
                        $currentDiff = array("left" => array(), "right" => array(), "prev_context" => array(), "post_context" => array());
                        $state = 'diff';
                    } else if ($state == 'marker') $state = 'diff';
                    if ($currentDiff) $currentDiff["left"][] = substr($d, 1);
                    break;

                default:
                    if ($state == 'diff') $state = 'post';
                    if ($currentDiff) {
                        if ($state == 'post') $currentDiff["post_context"][] = $d;
                        else $currentDiff["prev_context"][] = $d;
                    }
                    break;
            }
        }

        if ($currentDiff) $diffs[] = $currentDiff;
        /* iterate over diffs */
        $output = array();
        foreach ($diffs as $d) {
            foreach ($d['prev_context'] as $contextLine) {
                $output[] = array('', $contextLine, $contextLine);
            }

            if (count($d['left']) == 0) {
                $mode = 'added';
            } elseif (count($d['right']) == 0) {
                $mode = 'deleted';
            } else {
                $mode = 'modified';
            }

            for ($i = 0; $i < count($d['left']) || $i < count($d['right']); $i++) {
                $left = $i < count($d['left']) ? $d['left'][$i] : false;
                $right = $i < count($d['right']) ? $d['right'][$i] : false;
                $output[] = array($mode, $left, $right);
            }

            foreach ($d['post_context'] as $contextLine) {
                $output[] = array('', $contextLine, $contextLine);
            }
        }

        $this->diffDataSplit = $output;
        return $output;
    }

    /**
     * GetCommit
     *
     * Gets the commit for this filediff
     *
     * @access public
     * @return \GitPHP\Git\Commit object
     */
    public function GetCommit()
    {
        return $this->commit;
    }

    /**
     * SetCommit
     *
     * Sets the commit for this filediff
     *
     * @access public
     * @param mixed $commit commit object
     */
    public function SetCommit($commit)
    {
        $this->commit = $commit;
    }

    public function getDiffTooLarge()
    {
        return $this->diffTooLarge;
    }

    public function getDiffTreeLine()
    {
        return $this->diffTreeLine;
    }

    public function getDiffTypeImage()
    {
        return $this->diffTypeImage;
    }

    /**
     * Based on diff $this->diffData calculates diff to formatted base
     */
    public function diffWithFormattedBase(\GitPHP\Git\TmpDir $tmpdir, $fromTmpFile, $toTmpFile, $fromName, $toName)
    {
        $php_bin = '/local/php/bin/php';
        $phpcf_bin = '/local/utils/phpcf';
        if (!file_exists($php_bin) || !file_exists($phpcf_bin)) {
            return false;
        }
        /* Determine line numbers in old blob by diff */
        $lines = array();
        $chunk_old = 0;
        foreach (explode("\n", $this->diffData) as $line) {
            if (preg_match('#@@ \-(\d+),\d+ \+\d+,\d+ @@#', $line, $m)) {
                $chunk_old = $m[1];
            } else {
                if (substr($line, 0, 1) == ' ') {
                    $chunk_old++;
                } else if (substr($line, 0, 1) == '-' && substr($line, 0, 3) != '---') {
                    $lines[$chunk_old] = true;
                    $chunk_old++;
                }
            }
        }
        if (empty($lines)) {
            return false;
        }
        $lines = array_keys($lines);
        $lines = implode(',', $lines);

        $cmd = array(
            $php_bin,
            $phpcf_bin,
            'apply',
            $tmpdir->GetDir() . $fromTmpFile . ':' . $lines,
            ' 2>&1',
        );
        $cmd = implode(' ', $cmd);
        \GitPHP\Log::GetInstance()->timerStart();
        exec($cmd, $out, $ret);
        \GitPHP\Log::GetInstance()->timerStop('exec', $cmd . "\n\n" . implode("\n", $out) . ' ret:' . $ret);

        $this->diffData = \GitPHP\Git\DiffExe::Diff(
            (empty($fromTmpFile) ? null : ($tmpdir->GetDir() . $fromTmpFile)),
            $fromName,
            (empty($toTmpFile) ? null : ($tmpdir->GetDir() . $toTmpFile)),
            $toName,
            $this->DiffContext->getContext(),
            $this->DiffContext->getIgnoreWhitespace()
        );
        return true;
    }

    public function GetDecorationData()
    {
        return $this->decorationData;
    }

    public function SetDecorationData($data)
    {
        $this->decorationData = $data;
    }

    public function getInlineChanges()
    {
        return htmlspecialchars(json_encode($this->inline_changes));
    }
}
