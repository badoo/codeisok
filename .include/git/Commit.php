<?php

namespace GitPHP\Git;

class Commit extends GitObject
{
    /**
     * dataRead
     *
     * Indicates whether data for this commit has been read
     */
    protected $dataRead = false;

    /**
     * parents
     *
     * Array of parent commits
     */
    protected $parents = array();

    /**
     * tree
     *
     * Tree object for this commit
     *
     * @var \GitPHP\Git\Tree
     */
    protected $tree;

    /**
     * author
     *
     * Author for this commit
     */
    protected $author;

    /**
     * authorEpoch
     *
     * Author's epoch
     */
    protected $authorEpoch;

    /**
     * authorTimezone
     *
     * Author's timezone
     */
    protected $authorTimezone;

    /**
     * committer
     *
     * Committer for this commit
     */
    protected $committer;

    /**
     * committerEpoch
     *
     * Committer's epoch
     */
    protected $committerEpoch;

    /**
     * committerTimezone
     *
     * Committer's timezone
     */
    protected $committerTimezone;

    /**
     * title
     *
     * Stores the commit title
     */
    protected $title;

    /**
     * comment
     *
     * Stores the commit comment
     */
    protected $comment = array();

    /**
     * readTree
     *
     * Stores whether tree filenames have been read
     */
    protected $readTree = false;

    /**
     * blobPaths
     *
     * Stores blob hash to path mappings
     */
    protected $blobPaths = array();

    /**
     * treePaths
     *
     * Stores tree hash to path mappings
     */
    protected $treePaths = array();

    /**
     * hashPathsRead
     *
     * Stores whether hash paths have been read
     */
    protected $hashPathsRead = false;

    /**
     * containingTag
     *
     * Stores the tag containing the changes in this commit
     */
    protected $containingTag = null;

    /**
     * containingTagRead
     *
     * Stores whether the containing tag has been looked up
     */
    protected $containingTagRead = false;

    /**
     * parentsReferenced
     *
     * Stores whether the parents have been referenced into pointers
     */
    private $parentsReferenced = false;

    /**
     * treeReferenced
     *
     * Stores whether the tree has been referenced into a pointer
     */
    private $treeReferenced = false;

    private $reviews = null;

    /**
     * tags
     *
     * Commit tags cache
     */
    private $tags = null;

    /**
     * heads
     *
     * Commit heads cache
     */
    private $heads = null;

    /**
     * __construct
     *
     * Instantiates object
     *
     * @access public
     * @param mixed $project the project
     * @param string $hash object hash
     */
    public function __construct($project, $hash)
    {
        parent::__construct($project, $hash);
    }

    /**
     * GetParent
     *
     * Gets the main parent of this commit
     *
     * @access public
     * @return \GitPHP\Git\Commit commit object for parent
     */
    public function GetParent()
    {
        if (!$this->dataRead) $this->ReadData();

        if ($this->parentsReferenced) $this->DereferenceParents();

        if (isset($this->parents[0])) return $this->parents[0];
        return null;
    }

    /**
     * GetParents
     *
     * Gets an array of parent objects for this commit
     *
     * @access public
     * @return mixed array of commit objects
     */
    public function GetParents()
    {
        if (!$this->dataRead) $this->ReadData();

        if ($this->parentsReferenced) $this->DereferenceParents();

        return $this->parents;
    }

    /**
     * GetTree
     *
     * Gets the tree for this commit
     *
     * @access public
     * @return mixed tree object
     */
    public function GetTree()
    {
        if (!$this->dataRead) $this->ReadData();

        if ($this->treeReferenced) $this->DereferenceTree();

        return $this->tree;
    }

    /**
     * GetAuthor
     *
     * Gets the author for this commit
     *
     * @access public
     * @return string author
     */
    public function GetAuthor()
    {
        if (!$this->dataRead) $this->ReadData();

        return $this->author;
    }

    /**
     * GetAuthorName
     *
     * Gets the author's name only
     *
     * @access public
     * @return string author name
     */
    public function GetAuthorName()
    {
        if (!$this->dataRead) $this->ReadData();

        return preg_replace('/ <.*/', '', $this->author);
    }

    /**
     * GetAuthorEmail
     *
     * Gets the author's email only
     *
     * @access public
     * @return string author email
     */
    public function GetAuthorEmail()
    {
        if (!$this->dataRead) $this->ReadData();

        return rtrim(preg_replace('/.*</', '', $this->author), '>');
    }

    /**
     * GetAuthorEpoch
     *
     * Gets the author's epoch
     *
     * @access public
     * @return int author epoch
     */
    public function GetAuthorEpoch()
    {
        if (!$this->dataRead) $this->ReadData();

        return $this->authorEpoch;
    }

    /**
     * GetAuthorLocalEpoch
     *
     * Gets the author's local epoch
     *
     * @access public
     * @return string author local epoch
     */
    public function GetAuthorLocalEpoch()
    {
        $epoch = $this->GetAuthorEpoch();
        $tz = $this->GetAuthorTimezone();
        if (preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $tz, $regs)) {
            $local = $epoch + ((((int)$regs[1]) + ($regs[2] / 60)) * 3600);
            return $local;
        }
        return $epoch;
    }

    /**
     * GetAuthorTimezone
     *
     * Gets the author's timezone
     *
     * @access public
     * @return string author timezone
     */
    public function GetAuthorTimezone()
    {
        if (!$this->dataRead) $this->ReadData();

        return $this->authorTimezone;
    }

    /**
     * GetCommitter
     *
     * Gets the author for this commit
     *
     * @access public
     * @return string author
     */
    public function GetCommitter()
    {
        if (!$this->dataRead) $this->ReadData();

        return $this->committer;
    }

    /**
     * GetCommitterName
     *
     * Gets the author's name only
     *
     * @access public
     * @return string author name
     */
    public function GetCommitterName()
    {
        if (!$this->dataRead) $this->ReadData();

        return preg_replace('/ <.*/', '', $this->committer);
    }

    /**
     * GetCommitterEpoch
     *
     * Gets the committer's epoch
     *
     * @access public
     * @return int committer epoch
     */
    public function GetCommitterEpoch()
    {
        if (!$this->dataRead) $this->ReadData();

        return $this->committerEpoch;
    }

    /**
     * GetCommitterLocalEpoch
     *
     * Gets the committer's local epoch
     *
     * @access public
     * @return string committer local epoch
     */
    public function GetCommitterLocalEpoch()
    {
        $epoch = $this->GetCommitterEpoch();
        $tz = $this->GetCommitterTimezone();
        if (preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $tz, $regs)) {
            $local = $epoch + ((((int)$regs[1]) + ($regs[2] / 60)) * 3600);
            return $local;
        }
        return $epoch;
    }

    /**
     * GetCommitterTimezone
     *
     * Gets the author's timezone
     *
     * @access public
     * @return string author timezone
     */
    public function GetCommitterTimezone()
    {
        if (!$this->dataRead) $this->ReadData();

        return $this->committerTimezone;
    }

    /**
     * GetTitle
     *
     * Gets the commit title
     *
     * @access public
     * @param integer $trim length to trim to (0 for no trim)
     * @return string title
     */
    public function GetTitle($trim = 0)
    {
        if (!$this->dataRead) $this->ReadData();

        if (($trim > 0) && (strlen($this->title) > $trim)) {
            return substr($this->title, 0, $trim) . '…';
        }

        return $this->title;
    }

    /**
     * GetComment
     *
     * Gets the lines of comment
     *
     * @access public
     * @return array lines of comment
     */
    public function GetComment()
    {
        if (!$this->dataRead) $this->ReadData();

        return $this->comment;
    }

    /**
     * SearchComment
     *
     * Gets the lines of the comment matching the given pattern
     *
     * @access public
     * @param string $pattern pattern to find
     * @return array matching lines of comment
     */
    public function SearchComment($pattern)
    {
        if (empty($pattern)) return $this->GetComment();

        if (!$this->dataRead) $this->ReadData();

        return preg_grep('/' . $pattern . '/i', $this->comment);
    }

    /**
     * GetAge
     *
     * Gets the age of the commit
     *
     * @access public
     * @return string age
     */
    public function GetAge()
    {
        if (!$this->dataRead) $this->ReadData();

        if (!empty($this->committerEpoch)) return time() - $this->committerEpoch;

        return '';
    }

    /**
     * ReadData
     *
     * Read the data for the commit
     *
     * @access public
     * @param $external_contents
     */
    public function ReadData($external_contents = null)
    {
        $this->dataRead = true;

        if (is_null($external_contents)) {
            /* get data from git_rev_list */
            $exe = new \GitPHP\Git\GitExe($this->GetProject());
            $args = array();
            $args[] = '--header';
            $args[] = '--parents';
            $args[] = '--max-count=1';
            $args[] = $this->hash;
            $ret = $exe->Execute(GIT_REV_LIST, $args);
            unset($exe);

            $lines = explode("\n", $ret);
            array_shift($lines);
        } else {
            $lines = explode("\n", $external_contents);
        }

        if (!isset($lines[0])) return;

        foreach ($lines as $i => $line) {
            if (preg_match('/^tree ([0-9a-fA-F]{40})$/', $line, $regs)) {
                /* Tree */
                try {
                    $tree = $this->GetProject()->GetTree($regs[1]);
                    if ($tree) {
                        $tree->SetCommit($this);
                        $this->tree = $tree;
                    }
                } catch (\Exception $e) {}
            } else if (preg_match('/^author (.*) ([0-9]+) (.*)$/', $line, $regs)) {
                /* author data */
                $this->author = $regs[1];
                $this->authorEpoch = $regs[2];
                $this->authorTimezone = $regs[3];
            } else if (preg_match('/^committer (.*) ([0-9]+) (.*)$/', $line, $regs)) {
                /* committer data */
    	                $this->committer = $regs[1];
                $this->committerEpoch = $regs[2];
                $this->committerTimezone = $regs[3];
            } else if (preg_match('/^parent ([0-9a-fA-F]{40})/', $line, $regs)) {
                $this->parents[] = $this->GetProject()->GetCommit($regs[1]);
            } else if (!preg_match('/^[0-9a-fA-F]{40}/', $line)) {
                /* commit comment */
                $trimmed = trim($line);
                if (empty($this->title) && (strlen($trimmed) > 0)) $this->title = $trimmed;
                if (!empty($this->title)) {
                    if ((strlen($trimmed) > 0) || ($i < (count($lines) - 1))) $this->comment[] = $trimmed;
                }
            }
        }

        \GitPHP\Cache\Cache::GetInstance()->Set($this->GetCacheKey(), $this);
    }

    /**
     * GetHeads
     *
     * Gets heads that point to this commit
     * 
     * @access public
     * @return array array of heads
     */
    public function GetHeads()
    {
        if (is_array($this->heads)) return $this->heads;

        $this->heads = array();
        $projectRefs = $this->GetProject()->GetRefs('heads');

        foreach ($this->GetProject()->getHashHeads($this->hash) as $tag) {
            $key = 'refs/heads/' . $tag;
            if (!isset($projectRefs[$key])) continue;
            $this->heads[] = $projectRefs[$key];
        }

        return $this->heads;
    }

    /**
     * GetTags
     *
     * Gets tags that point to this commit
     *
     * @access public
     * @return \GitPHP\Git\Tag[] array of tags
     */
    public function GetTags()
    {
        if (is_array($this->tags)) return $this->tags;

        $this->tags = array();

        $projectRefs = $this->GetProject()->GetRefs('tags');

        foreach ($this->GetProject()->getHashTags($this->hash) as $tag) {
            $key = 'refs/tags/' . $tag;
            if (!isset($projectRefs[$key])) continue;
            $this->tags[] = $projectRefs[$key];
        }

        return $this->tags;
    }

    /**
     * GetContainingTag
     *
     * Gets the tag that contains the changes in this commit
     *
     * @access public
     * @return \GitPHP\Git\Tag tag object
     */
    public function GetContainingTag()
    {
        if (!$this->containingTagRead) $this->ReadContainingTag();

        return $this->containingTag;
    }

    /**
     * ReadContainingTag
     *
     * Looks up the tag that contains the changes in this commit
     *
     * @access private
     */
    public function ReadContainingTag()
    {
        $this->containingTagRead = true;

        $exe = new \GitPHP\Git\GitExe($this->GetProject());
        $args = array();
        $args[] = '--tags';
        $args[] = $this->hash;
        $revs = explode("\n", $exe->Execute(GIT_NAME_REV, $args));

        foreach ($revs as $revline) {
            if (preg_match('/^([0-9a-fA-F]{40})\s+tags\/(.+)(\^[0-9]+|\~[0-9]+)$/', $revline, $regs)) {
                if ($regs[1] == $this->hash) {
                    $this->containingTag = $this->GetProject()->GetTag($regs[2]);
                    break;
                }
            }
        }

        \GitPHP\Cache\Cache::GetInstance()->Set($this->GetCacheKey(), $this);
    }

    /**
     * DiffToParent
     *
     * Diffs this commit with its immediate parent
     *
     * @access public
     * @return \GitPHP\Git\TreeDiff Tree diff
     */
    public function DiffToParent()
    {
        return new \GitPHP\Git\TreeDiff($this->GetProject(), $this->hash, '', new \GitPHP\Git\DiffContext());
    }

    /**
     * PathToHash
     *
     * Given a filepath, get its hash
     *
     * @access public
     * @param string $path path
     * @return string hash
     */
    public function PathToHash($path)
    {
        if (empty($path)) {
            return '';
        }

        if (!$this->hashPathsRead) {
            $this->ReadHashPaths();
        }

        if (isset($this->blobPaths[$path])) {
            return $this->blobPaths[$path];
        }

        if (isset($this->treePaths[$path])) {
            return $this->treePaths[$path];
        }

        return '';
    }

    /**
     * ReadHashPaths
     *
     * Read hash to path mappings
     *
     * @access private
     */
    private function ReadHashPaths()
    {
        $this->hashPathsRead = true;

        $exe = new \GitPHP\Git\GitExe($this->GetProject());

        $args = array();
        $args[] = '--full-name';
        $args[] = '-r';
        $args[] = '-t';
        $args[] = $this->hash;

        $lines = explode("\n", $exe->Execute(GIT_LS_TREE, $args));

        foreach ($lines as $line) {
            if (preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)$/", $line, $regs)) {
                switch ($regs[2]) {
                    case 'tree':
                        $this->treePaths[trim($regs[4])] = $regs[3];
                        break;

                    case 'blob';
                        $this->blobPaths[trim($regs[4])] = $regs[3];
                        break;
                }
            }
        }

        \GitPHP\Cache\Cache::GetInstance()->Set($this->GetCacheKey(), $this);
    }

    /**
     * SearchFilenames
     *
     * Returns array of objects matching pattern
     *
     * @access public
     * @param string $pattern pattern to find
     * @return array array of objects
     */
    public function SearchFilenames($pattern)
    {
        if (empty($pattern)) {
            return null;
        }

        if (!$this->hashPathsRead) $this->ReadHashPaths();

        $results = array();

        foreach ($this->treePaths as $path => $hash) {
            if (preg_match('/' . preg_quote($pattern, '/') . '/i', $path)) {
                $obj = $this->GetProject()->GetTree($hash);
                $obj->SetCommit($this);
                $results[$path] = $obj;
            }
        }

        foreach ($this->blobPaths as $path => $hash) {
            if (preg_match('/' . preg_quote($pattern, '/') . '/i', $path)) {
                $obj = $this->GetProject()->GetBlob($hash);
                $obj->SetCommit($this);
                $results[$path] = $obj;
            }
        }

        ksort($results);

        return $results;
    }

    /**
     * SearchFileContents
     *
     * Searches for a pattern in file contents
     *
     * @access public
     * @param string $pattern pattern to search for
     * @return array multidimensional array of results
     */
    public function SearchFileContents($pattern)
    {
        if (empty($pattern)) {
            return null;
        }

        $exe = new \GitPHP\Git\GitExe($this->GetProject());

        $args = array();
        $args[] = '-I';
        $args[] = '--full-name';
        $args[] = '--ignore-case';
        $args[] = '-n';
        $args[] = '-e';
        $args[] = escapeshellarg($pattern);
        $args[] = $this->hash;

        $lines = explode("\n", $exe->Execute(GIT_GREP, $args));

        $results = array();

        foreach ($lines as $line) {
            if (preg_match('/^[^:]+:([^:]+):([0-9]+):(.+)$/', $line, $regs)) {
                if (!isset($results[$regs[1]]['object'])) {
                    $hash = $this->PathToHash($regs[1]);
                    if (!empty($hash)) {
                        $obj = $this->GetProject()->GetBlob($hash);
                        $obj->SetCommit($this);
                        $results[$regs[1]]['object'] = $obj;
                    }
                }
                $results[$regs[1]]['lines'][(int)($regs[2])] = $regs[3];
            }
        }

        return $results;
    }

    /**
     * SearchFiles
     *
     * Searches filenames and file contents for a pattern
     *
     * @access public
     * @param string $pattern pattern to search
     * @param integer $count number of results to get
     * @param integer $skip number of results to skip
     * @return array array of results
     */
    public function SearchFiles($pattern, $count = 100, $skip = 0)
    {
        if (empty($pattern)) {
            return null;
        }

        $grepresults = $this->SearchFileContents($pattern);

        $nameresults = $this->SearchFilenames($pattern);

        /* Merge the results together */
        foreach ($nameresults as $path => $obj) {
            if (!isset($grepresults[$path]['object'])) {
                $grepresults[$path]['object'] = $obj;
                $grepresults[$path]['lines'] = array();
            }
        }

        ksort($grepresults);

        return array_slice($grepresults, $skip, $count, true);
    }

    /**
     * ReferenceParents
     *
     * Turns the list of parents into reference pointers
     *
     * @access private
     */
    private function ReferenceParents()
    {
        if ($this->parentsReferenced) return;

        if ((!isset($this->parents)) || (count($this->parents) < 1)) return;

        for ($i = 0; $i < count($this->parents); $i++) {
            $this->parents[$i] = $this->parents[$i]->GetHash();
        }

        $this->parentsReferenced = true;
    }

    /**
     * DereferenceParents
     *
     * Turns the list of parent pointers back into objects
     *
     * @access private
     */
    private function DereferenceParents()
    {
        if (!$this->parentsReferenced) return;

        if ((!$this->parents) || (count($this->parents) < 1)) return;

        for ($i = 0; $i < count($this->parents); $i++) {
            $this->parents[$i] = $this->GetProject()->GetCommit($this->parents[$i]);
        }

        $this->parentsReferenced = false;
    }

    /**
     * ReferenceTree
     *
     * Turns the tree into a reference pointer
     *
     * @access private
     */
    private function ReferenceTree()
    {
        if ($this->treeReferenced) return;

        if (!$this->tree) return;

        $this->tree = $this->tree->GetHash();

        $this->treeReferenced = true;
    }

    /**
     * DereferenceTree
     *
     * Turns the tree pointer back into an object
     *
     * @access private
     */
    private function DereferenceTree()
    {
        if (!$this->treeReferenced) return;

        if (empty($this->tree)) return;

        $this->tree = $this->GetProject()->GetTree($this->tree);

        if ($this->tree) $this->tree->SetCommit($this);

        $this->treeReferenced = false;
    }

    /**
     * __sleep
     *
     * Called to prepare the object for serialization
     *
     * @access public
     * @return array list of properties to serialize
     */
    public function __sleep()
    {
        if (!$this->parentsReferenced) {
            $this->ReferenceParents();
        }

        if (!$this->treeReferenced) {
            $this->ReferenceTree();
        }

        $properties = array(
            'dataRead', 'parents', 'tree', 'author', 'authorEpoch', 'authorTimezone', 'committer', 'committerEpoch',
            'committerTimezone', 'title', 'comment', 'readTree', 'blobPaths', 'treePaths', 'hashPathsRead',
            'parentsReferenced', 'treeReferenced'
        );
        return array_merge($properties, parent::__sleep());
    }

    /**
     * GetCacheKey
     *
     * Gets the cache key to use for this object
     *
     * @access public
     * @return string cache key
     */
    public function GetCacheKey()
    {
        $key = parent::GetCacheKey();
        if (!empty($key)) $key .= '|';

        $key .= 'commit|' . $this->hash;

        return $key;
    }

    public function getReviews()
    {
        return $this->reviews;
    }

    public function setReview($review)
    {
        $this->reviews[$review['review_id']] = $review;
    }
}
