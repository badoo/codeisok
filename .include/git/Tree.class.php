<?php
/**
 * GitPHP Tree
 *
 * Represents a single tree
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * Tree class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Tree extends GitPHP_FilesystemObject
{
    /**
     * contents
     *
     * Tree contents
     *
     * @access protected
     */
    protected $contents = array();

    /**
     * contentsRead
     *
     * Stores whether contents were read
     *
     * @access protected
     */
    protected $contentsRead = false;

    /**
     * contentsReferenced
     *
     * Stores whether contents have been referenced into pointers
     *
     * @access private
     */
    private $contentsReferenced = false;

    /**
     * __construct
     *
     * Instantiates object
     *
     * @access public
     * @param mixed $project the project
     * @param string $hash tree hash
     * @return mixed tree object
     * @throws Exception exception on invalid hash
     */
    public function __construct($project, $hash)
    {
        parent::__construct($project, $hash);
    }

    /**
     * SetCommit
     *
     * Sets the commit for this tree (overrides base)
     *
     * @access public
     * @param mixed $commit commit object
     */
    public function SetCommit($commit)
    {
        parent::SetCommit($commit);

        if ($this->contentsRead && !$this->contentsReferenced) {
            foreach ($this->contents as $obj) {
                $obj->SetCommit($commit);
            }
        }
    }

    /**
     * GetContents
     *
     * Gets the tree contents
     *
     * @access public
     * @return array array of objects for contents
     */
    public function GetContents()
    {
        if (!$this->contentsRead) $this->ReadContents();

        if ($this->contentsReferenced) $this->DereferenceContents();

        return $this->contents;
    }

    /**
     * ReadContents
     *
     * Reads the tree contents
     *
     * @access protected
     */
    protected function ReadContents()
    {
        $this->contentsRead = true;

        $exe = new GitPHP_GitExe($this->GetProject());

        $args = array();
        $args[] = '--full-name';
        if ($exe->CanShowSizeInTree()) $args[] = '-l';
        $args[] = '-t';
        $args[] = $this->hash;

        $lines = explode("\n", $exe->Execute(GIT_LS_TREE, $args));
        $contents = ['t' => [], 'b' => []];
        foreach ($lines as $line) {
            if (preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})(\s+[0-9]+|\s+-)?\t(.+)$/", $line, $regs)) {
                switch ($regs[2]) {
                    case 'tree':
                        $t = $this->GetProject()->GetTree($regs[3]);
                        //$t->SetMode($regs[1]);
                        $path = $regs[5];
                        if (!empty($this->path)) $path = $this->path . '/' . $path;
                        $t->SetPath($path);
                        if ($this->commit) $t->SetCommit($this->commit);
                        $contents['t'][] = $t;
                        break;

                    case 'blob':
                        $b = $this->GetProject()->GetBlob($regs[3]);
                        //$b->SetMode($regs[1]);
                        $path = $regs[5];
                        if (!empty($this->path)) $path = $this->path . '/' . $path;
                        $b->SetPath($path);
                        $size = trim($regs[4]);
                        if (!empty($size)) $b->SetSize(\GitPHP\Util::humanFilesize($regs[4]));
                        if ($this->commit) $b->SetCommit($this->commit);
                        $contents['b'][] = $b;
                        break;
                }
            }
        }
        $this->contents = array_merge($contents['t'], $contents['b']);
        GitPHP_Cache::GetInstance()->Set($this->GetCacheKey(), $this);
    }

    /**
     * ReferenceContents
     *
     * Turns the contents objects into reference pointers
     *
     * @access private
     */
    private function ReferenceContents()
    {
        if ($this->contentsReferenced) return;

        if (!(isset($this->contents) && (count($this->contents) > 0))) return;

        for ($i = 0; $i < count($this->contents); ++$i) {
            $obj = $this->contents[$i];
            $data = array();

            $data['hash'] = $obj->GetHash();
            $data['mode'] = $obj->GetMode();
            $data['path'] = $obj->GetPath();

            if ($obj instanceof GitPHP_Tree) {
                $data['type'] = 'tree';
            } else if ($obj instanceof \GitPHP\Git\Blob) {
                $data['type'] = 'blob';
                $data['size'] = $obj->GetSize();
            }

            $this->contents[$i] = $data;
        }

        $this->contentsReferenced = true;
    }

    /**
     * DereferenceContents
     *
     * Turns the contents pointers back into objects
     *
     * @access private
     */
    private function DereferenceContents()
    {
        if (!$this->contentsReferenced) return;

        if (!(isset($this->contents) && (count($this->contents) > 0))) return;

        for ($i = 0; $i < count($this->contents); ++$i) {
            $data = $this->contents[$i];
            $obj = null;

            if (!isset($data['hash']) || empty($data['hash'])) continue;

            if ($data['type'] == 'tree') {
                $obj = $this->GetProject()->GetTree($data['hash']);
            } else if ($data['type'] == 'blob') {
                $obj = $this->GetProject()->GetBlob($data['hash']);
                if (isset($data['size']) && !empty($data['size'])) $obj->SetSize($data['size']);
            } else {
                continue;
            }

            if (isset($data['mode']) && !empty($data['mode'])) $obj->SetMode($data['mode']);

            if (isset($data['path']) && !empty($data['path'])) $obj->SetPath($data['path']);

            if ($this->commit) $obj->SetCommit($this->commit);

            $this->contents[$i] = $obj;
        }

        $this->contentsReferenced = false;
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
        if (!$this->contentsReferenced) $this->ReferenceContents();

        $properties = array('contents', 'contentsRead', 'contentsReferenced');
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

        $key .= 'tree|' . $this->hash;

        return $key;
    }
}
