<?php
/**
 * GitPHP Tree Diff
 *
 * Represents differences between two commit trees
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * TreeDiff class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_TreeDiff implements Iterator
{
    /**
     * fromHash
     *
     * Stores the from hash
     */
    protected $fromHash;

    /**
     * toHash
     *
     * Stores the to hash
     */
    protected $toHash;

    /**
     * renames
     *
     * Stores whether to detect renames
     */
    protected $renames;

    /**
     * project
     *
     * Stores the project
     */
    protected $project;

    /**
     * fileDiffs
     *
     * Stores the individual file diffs
     * @var GitPHP_FileDiff[]
     */
    protected $fileDiffs = array();

    /**
     * dataRead
     *
     * Stores whether data has been read
     */
    protected $dataRead = false;

    /**
     * @var DiffContext
     */
    protected $DiffContext;

    /**
     * @param GitPHP_Project $project project
     * @param string $toHash to commit hash
     * @param string $fromHash from commit hash
     * @param DiffContext $DiffContext
     * @throws GitPHP_MessageException
     */
    public function __construct($project, $toHash, $fromHash = '', DiffContext $DiffContext)
    {
        $this->project = $project;
        $this->DiffContext = $DiffContext;

        $toCommit = $this->project->GetCommit($toHash);
        if (empty($toCommit)) {
            throw new GitPHP_MessageException('Commit not found ' . $toHash);
        }
        $this->toHash = $toHash;

        if (empty($fromHash)) {
            $parent = $toCommit->GetParent();
            if ($parent) {
                $this->fromHash = $parent->GetHash();
            }
        } else {
            $this->fromHash = $fromHash;
        }
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

        $exe = new GitPHP_GitExe($this->project);

        $args = array();

        $args[] = '-r';
        if ($this->DiffContext->getRenames()) $args[] = '-M';

        if (empty($this->fromHash)) $args[] = '--root';
        else $args[] = $this->fromHash;

        $args[] = $this->toHash;

        $diffTreeLines = explode("\n", $exe->Execute(GIT_DIFF_TREE, $args));
        $toHash = $this->fromHash ? '' : $this->toHash;
        foreach ($diffTreeLines as $line) {
            $trimmed = trim($line);
            if ((strlen($trimmed) > 0) && (substr_compare($trimmed, ':', 0, 1) === 0)) {
                try {
                    $this->fileDiffs[] = new GitPHP_FileDiff($this->project, $trimmed, $toHash, $this->DiffContext);
                } catch (Exception $e) {}
            }
        }

        unset($exe);
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

    /**
     * GetRenames
     *
     * Get whether this treediff is set to detect renames
     *
     * @access public
     * @return boolean true if renames will be detected
     */
    public function GetRenames()
    {
        return $this->renames;
    }

    /**
     * SetRenames
     *
     * Set whether this treediff is set to detect renames
     *
     * @access public
     * @param boolean $renames whether to detect renames
     */
    public function SetRenames($renames)
    {
        if ($renames == $this->renames) return;

        $this->renames = $renames;
        $this->dataRead = false;
    }

    /**
     * rewind
     *
     * Rewinds the iterator
     */
    function rewind()
    {
        if (!$this->dataRead) $this->ReadData();

        return reset($this->fileDiffs);
    }

    /**
     * current
     *
     * Returns the current element in the array
     */
    function current()
    {
        if (!$this->dataRead) $this->ReadData();

        return current($this->fileDiffs);
    }

    /**
     * key
     *
     * Returns the current key
     */
    function key()
    {
        if (!$this->dataRead) $this->ReadData();

        return key($this->fileDiffs);
    }

    /**
     * next
     *
     * Advance the pointer
     */
    function next()
    {
        if (!$this->dataRead) $this->ReadData();

        return next($this->fileDiffs);
    }

    /**
     * valid
     *
     * Test for a valid pointer
     */
    function valid()
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

    public function ToArray()
    {
        if (!$this->dataRead) $this->ReadData();

        return $this->fileDiffs;
    }
}
