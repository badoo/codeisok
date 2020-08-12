<?php

namespace GitPHP\Git;

class Head extends \GitPHP\Git\Ref
{
    /**
     * commit
     *
     * Stores the commit internally
     *
     * @access protected
     */
    protected $commit;

    /**
     * __construct
     *
     * Instantiates head
     *
     * @access public
     * @param mixed $project the project
     * @param string $head head name
     * @param string $headHash head hash
     * @return mixed head object
     * @throws \Exception exception on invalid head or hash
     */
    public function __construct($project, $head, $headHash = '')
    {
        parent::__construct($project, 'heads', $head, $headHash);
    }

    /**
     * GetCommit
     *
     * Gets the commit for this head
     *
     * @access public
     * @return \GitPHP\Git\Commit
     */
    public function GetCommit()
    {
        if (!$this->commit) {
            $this->commit = $this->project->GetCommit($this->GetHash());
        }

        return $this->commit;
    }

    /**
     * CompareAge
     *
     * Compares two heads by age
     *
     * @access public
     * @static
     * @param self $a first head
     * @param self $b second head
     * @return integer comparison result
     */
    public static function CompareAge($a, $b)
    {
        $aObj = $a->GetCommit();
        $bObj = $b->GetCommit();
        if ($aObj->GetAge() === $bObj->GetAge()) return 0;
        return ($aObj->GetAge() < $bObj->GetAge() ? -1 : 1);
    }

    /**
     * Checks that head exists in repository
     * 
     * @return bool
     */
    public function Exists()
    {
        try {
            $this->GetHash();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
