<?php

namespace GitPHP\Git;

class BranchDiff implements \Iterator
{
    /**
     * fromHash
     *
     * Stores the from hash
     *
     * @access protected
     */
    protected $fromBranch;

    /**
     * toHash
     *
     * Stores the to hash
     *
     * @access protected
     */
    protected $toBranch;

    /**
     * project
     *
     * Stores the project
     *
     * @var \GitPHP_Project
     * @access protected
     */
    protected $project;

    /**
     * fileDiffs
     *
     * Stores the individual file diffs
     *
     * @access protected
     * @var \GitPHP\Git\FileDiff[]
     */
    protected $fileDiffs = array();

    /**
     * dataRead
     *
     * Stores whether data has been read
     *
     * @var bool
     * @access protected
     */
    protected $dataRead = false;

    /**
     * @var \GitPHP_GitExe
     */
    private $exe;

    /**
     * @var string
     */
    private $fromHash;

    /**
     * @var string
     */
    private $toHash;

    /**
     * @var \DiffContext
     */
    private $DiffContext;

    private $has_hidden;

    /**
     * @param \GitPHP_Project $project project
     * @param $toBranch
     * @param string $fromBranch
     * @param \DiffContext $DiffContext
     */
    public function __construct($project, $toBranch, $fromBranch, \DiffContext $DiffContext)
    {
        $this->project = $project;

        $this->toBranch = $toBranch;
        $this->fromBranch = ($fromBranch) ? : 'master';
        $this->DiffContext = $DiffContext;

        $this->exe = new \GitPHP_GitExe($this->project);
    }

    public function setFromHash($fromHash)
    {
        $this->fromHash = $fromHash;
    }

    public function getBaseHash()
    {
        if (empty($this->toHash)) {
            $this->toHash = trim($this->exe->Execute(GIT_REV_PARSE, array($this->toBranch)));
        }
        $diff_base_hash = trim($this->exe->Execute(GIT_MERGE_BASE, array($this->fromBranch, $this->toHash)));

        if ($this->toHash != $diff_base_hash) {
            $args = array(
                '-1',
                '--format="%s"',
                $diff_base_hash,
            );
            $diff_base_message = trim($this->exe->Execute(GIT_LOG, $args));

            $ticket = $this->toBranch;
            if (preg_match('#([A-Z]+\-[0-9]+)#', $this->toBranch, $m)) {
                $ticket = $m[1];
            }

            /* if merge-base commit message contains reference to something similiar to branch ticket
             then this is not final base hash and use scanning algo */
            \GitPHP\Log::GetInstance()->Log(__METHOD__, $ticket);
            if (stripos($diff_base_message, $ticket) === false) return $diff_base_hash;
        }

        $args = [
            '--ancestry-path',
            '--parents',
            '--first-parent',
            "{$this->toHash}..{$this->fromBranch}",
            '2>/dev/null',
        ];
        $history = trim($this->exe->Execute(GIT_REV_LIST, $args));
        if (empty($history)) {
            // it might be so that we won't be able to get history with --first-parent
            // if there is "brother" commit for merge commit that we're looking for
            $args = [
                '--ancestry-path',
                '--parents',
                "{$this->toHash}..{$this->fromBranch}",
                '2>/dev/null',
            ];
            $history = trim($this->exe->Execute(GIT_REV_LIST, $args));
        }
        $history = array_reverse(explode(PHP_EOL, $history));
        $look_for = $this->toHash;
        foreach ($history as $commit) {
            $hashes = explode(' ', $commit);
            if (in_array($look_for, array_slice($hashes, 2))) {
                // this is merge commit and $look_for is from the merged branch (not the first-parent)
                // this means that first-parent commit is what we are looking for
                $base_commit = $hashes[1];
                $look_for = $base_commit; // it might be so that we're not in the master branch yet
            } else if ($look_for === $hashes[1]) {
                // we should go forward the tree and look for next commit in order to find when it was merged into master
                $look_for = $hashes[0];
            } // actually, with --ancestry-path there just couldn't be anything `} else {`
        }

        if (!isset($base_commit)) return $diff_base_hash;
        $diff_base_hash = trim($this->exe->Execute(GIT_MERGE_BASE, array($base_commit, $this->toHash)));
        return $diff_base_hash;
    }

    /**
     * ReadData
     *
     * Reads the tree diff data
     *
     * @access private
     */
    private function ReadData()
    {
        $this->dataRead = true;

        $this->fileDiffs = array();

        if (empty($this->toHash)) {
            $this->toHash = trim($this->exe->Execute(GIT_REV_PARSE, array($this->toBranch)));
        }
        if (empty($this->fromHash)) {
            $this->fromHash = $this->getBaseHash();
        }

        $args = array();
        $args[] = '-r';
        if ($this->DiffContext->getRenames()) $args[] = '-M';

        $args[] = $this->fromHash;
        $args[] = $this->toHash;

        $hide_files_per_category = \GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::HIDE_FILES_PER_CATEGORY, []);
        $diffBranchLines = explode("\n", $this->exe->Execute(GIT_DIFF_TREE, $args));
        foreach ($diffBranchLines as $line) {
            $trimmed = trim($line);
            if ((strlen($trimmed) > 0) && (substr_compare($trimmed, ':', 0, 1) === 0)) {
                try {
                    $fileDiff = new \GitPHP\Git\FileDiff($this->project, $trimmed, $this->fromHash, $this->DiffContext, $this->toHash);
                    if (!$this->DiffContext->getShowHidden() && isset($hide_files_per_category[$this->project->GetCategory()])) {
                        foreach ($hide_files_per_category[$this->project->GetCategory()] as $pattern) {
                            if (preg_match($pattern, $fileDiff->GetFromFile()) || preg_match($pattern, $fileDiff->GetToFile())) {
                                $this->has_hidden = true;
                                continue 2;
                            }
                        }
                    }
                    $this->fileDiffs[] = $fileDiff;
                } catch (\Exception $e) {}
            }
        }
    }

    /**
     * GetFromHash
     *
     * Gets the from hash for this treediff
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
     * Gets the to hash for this treediff
     *
     * @access public
     * @return string to hash
     */
    public function GetToHash()
    {
        return $this->toHash;
    }

    public function SetToHash($toHash)
    {
        $this->toHash = $toHash;
    }

    /**
     * rewind
     *
     * Rewinds the iterator
     */
    public function rewind()
    {
        if (!$this->dataRead) $this->ReadData();

        return reset($this->fileDiffs);
    }

    /**
     * current
     *
     * Returns the current element in the array
     */
    public function current()
    {
        if (!$this->dataRead) $this->ReadData();

        return current($this->fileDiffs);
    }

    /**
     * key
     *
     * Returns the current key
     */
    public function key()
    {
        if (!$this->dataRead) $this->ReadData();

        return key($this->fileDiffs);
    }

    /**
     * next
     *
     * Advance the pointer
     */
    public function next()
    {
        if (!$this->dataRead) $this->ReadData();

        return next($this->fileDiffs);
    }

    /**
     * valid
     *
     * Test for a valid pointer
     */
    public function valid()
    {
        if (!$this->dataRead) $this->ReadData();

        return key($this->fileDiffs) !== null;
    }

    /**
     * Count
     *
     * Gets the number of file changes in this treediff
     *
     * @access public
     * @return integer count of file changes
     */
    public function Count()
    {
        if (!$this->dataRead) $this->ReadData();

        return count($this->fileDiffs);
    }

    public function hasHidden()
    {
        return $this->has_hidden;
    }
}
