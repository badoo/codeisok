<?php
/**
 * GitPHP Project
 * 
 * Represents a single git project
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * Project class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Project
{
    /**
     * project
     *
     * Stores the project internally
     *
     * @access protected
     */
    protected $project;

    /**
     * owner
     *
     * Stores the owner internally
     *
     * @access protected
     */
    protected $owner = "";

    /**
     * readOwner
     *
     * Stores whether the file owner has been read
     *
     * @access protected
     */
    protected $readOwner = false;

    /**
     * description
     *
     * Stores the description internally
     *
     * @access protected
     */
    protected $description;

    /**
     * readDescription
     *
     * Stores whether the description has been
     * read from the file yet
     *
     * @access protected
     */
    protected $readDescription = false;

    /**
     * category
     *
     * Stores the category internally
     *
     * @access protected
     */
    protected $category = '';

    /**
     * epoch
     *
     * Stores the project epoch internally
     *
     * @access protected
     */
    protected $epoch;

    /**
     * epochRead
     *
     * Stores whether the project epoch has been read yet
     *
     * @access protected
     */
    protected $epochRead = false;

    /**
     * Stores symbolic-ref HEAD destination branch
     *
     * @var string
     */
    protected $default_branch;

    /**
     * head
     *
     * Stores the head hash internally
     *
     * @access protected
     */
    protected $head;

    /**
     * readHeadRef
     *
     * Stores whether the head ref has been read yet
     *
     * @access protected
     */
    protected $readHeadRef = false;

    /**
     * tags
     *
     * Stores the tags for the project
     *
     * @access protected
     */
    protected $tags = array();

    /**
     * heads
     *
     * Stores the heads for the project
     *
     * @access protected
     */
    protected $heads = array();

    /**
     * all hashes
     *
     * Inverted index to store fast "hash => info" map
     *
     * @var array
     */
    protected $all_hashes = array();

    /**
     * readRefs
     *
     * Stores whether refs have been read yet
     *
     * @access protected
     */
    protected $readRefs = false;

    /**
     * cloneUrl
     *
     * Stores the clone url internally
     *
     * @access protected
     */
    protected $cloneUrl = null;

    /**
     * pushUrl
     *
     * Stores the push url internally
     *
     * @access protected
     */
    protected $pushUrl = null;

    /**
     * bugUrl
     *
     * Stores the bug url internally
     *
     * @access protected
     */
    protected $bugUrl = null;

    /**
     * bugPattern
     *
     * Stores the bug pattern internally
     *
     * @access protected
     */
    protected $bugPattern = null;

    /**
     * commitCache
     *
     * Caches fetched commit objects in case of
     * repeated requests for the same object
     *
     * @access protected
     */
    protected $commitCache = array();

    /**
     * Email to notify comments
     *
     *
     * @access protected
     */
    protected $notify_email = "";

    /**
     * __construct
     *
     * Class constructor
     *
     * @access public
     * @param string $project
     * @throws Exception if project is invalid or outside of projectroot
     */
    public function __construct($project)
    {
        $User = GitPHP_Session::instance()->getUser();
        $Acl = \GitPHP\Acl::getInstance();
        if (!$Acl->isProjectAllowed($project, $User)) {
            throw new \Exception();
        }
        $this->SetProject($project);
    }

    /**
     * SetProject
     *
     * Attempts to set the project
     *
     * @param $project
     * @throws Exception if project is invalid or outside of projectroot
     */
    private function SetProject($project)
    {
        $projectRoot = GitPHP_Util::AddSlash(\GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::PROJECT_ROOT));

        $realProjectRoot = realpath($projectRoot);
        $path = $projectRoot . $project;
        $fullPath = realpath($path);

        if (!is_dir($fullPath)) {
            throw new Exception(sprintf(__('%1$s is not a directory'), $project));
        }

        if (!is_file($fullPath . '/HEAD')) {
            throw new Exception(sprintf(__('%1$s is not a git repository'), $project));
        }

        if (preg_match('/(^|\/)\.{0,2}(\/|$)/', $project)) {
            throw new Exception(sprintf(__('%1$s is attempting directory traversal'), $project));
        }

        $pathPiece = substr($fullPath, 0, strlen($realProjectRoot));

        if ((!is_link($path)) && (strcmp($pathPiece, $realProjectRoot) !== 0)) {
            throw new Exception(sprintf(__('%1$s is outside of the projectroot'), $project));
        }

        $this->project = $project;
    }

    /**
     * Check User for permission to perform an action on this project
     * @param string $action
     * @param \GitPHP_User|null $User
     * @return bool
     */
    public function isActionAllowed($action, $User = null)
    {
        return \GitPHP\Acl::getInstance()->isActionAllowed($this, $action, $User);
    }

    /**
     * GetOwner
     *
     * Gets the project's owner
     *
     * @access public
     * @return string project owner
     */
    public function GetOwner()
    {
        if (empty($this->owner) && !$this->readOwner) {
            $exe = new GitPHP_GitExe($this);
            $args = array();
            $args[] = 'gitweb.owner';
            $this->owner = $exe->Execute(GIT_CONFIG, $args);
            unset($exe);

            if (empty($this->owner) && function_exists('posix_getpwuid')) {
                $uid = fileowner($this->GetPath());
                if ($uid !== false) {
                    $data = posix_getpwuid($uid);
                    if (isset($data['gecos']) && !empty($data['gecos'])) {
                        $this->owner = $data['gecos'];
                    } elseif (isset($data['name']) && !empty($data['name'])) {
                        $this->owner = $data['name'];
                    }
                }
            }

            $this->readOwner = true;
        }

        return $this->owner;
    }

    /**
     * SetOwner
     *
     * Sets the project's owner (from an external source)
     *
     * @access public
     * @param string $owner the owner
     */
    public function SetOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * GetProject
     *
     * Gets the project
     *
     * @access public
     * @return string - the project (repository name)
     */
    public function GetProject()
    {
        return $this->project;
    }

    /**
     * GetSlug
     *
     * Gets the project as a filename/url friendly slug
     *
     * @access public
     * @return string the slug
     */
    public function GetSlug()
    {
        $from = array(
            '/',
            '.git'
        );
        $to = array(
            '-',
            ''
        );
        return str_replace($from, $to, $this->project);
    }

    /**
     * GetPath
     *
     * Gets the full project path
     *
     * @access public
     * @return string project path
     */
    public function GetPath()
    {
        $projectRoot = GitPHP_Util::AddSlash(\GitPHP\Config::GetInstance()->GetValue(GitPHP\Config::PROJECT_ROOT));

        return $projectRoot . $this->project;
    }

    /**
     * GetDescription
     *
     * Gets the project description
     *
     * @access public
     * @param int $trim length to trim description to (0 for no trim)
     * @return string project description
     */
    public function GetDescription($trim = 0)
    {
        if (!$this->readDescription) {
            $this->description = @file_get_contents($this->GetPath() . '/description');
            if ($this->description === false) {
                \GitPHP\Log::GetInstance()->Log('Could not get description for project ' . $this->project);
            }
        }

        if (($trim > 0) && (strlen($this->description) > $trim)) {
            return substr($this->description, 0, $trim) . '…';
        }

        return $this->description;
    }

    /**
     * SetDescription
     *
     * Overrides the project description
     *
     * @access public
     * @param string $descr description
     */
    public function SetDescription($descr)
    {
        $this->description = $descr;
        $this->readDescription = true;
    }

    /**
     * GetDaemonEnabled
     *
     * Returns whether gitdaemon is allowed for this project
     *
     * @access public
     * @return boolean git-daemon-export-ok?
     */
    public function GetDaemonEnabled()
    {
        return file_exists($this->GetPath() . '/git-daemon-export-ok');
    }

    /**
     * GetCategory
     *
     * Gets the project's category
     *
     * @access public
     * @return string category
     */
    public function GetCategory()
    {
        return $this->category;
    }

    /**
     * SetCategory
     * 
     * Sets the project's category
     *
     * @access public
     * @param string $category category
     */
    public function SetCategory($category)
    {
        $this->category = $category;
    }

    /**
     * GetCloneUrl
     *
     * Gets the clone URL for this repository, if specified
     *
     * @access public
     * @return string clone url
     */
    public function GetCloneUrl()
    {
        if ($this->cloneUrl !== null) return $this->cloneUrl;

        $cloneurl = GitPHP_Util::AddSlash(\GitPHP\Config::GetInstance()->GetValue('cloneurl', ''), false);
        if (!empty($cloneurl)) $cloneurl .= $this->project;

        return $cloneurl;
    }

    /**
     * SetCloneUrl
     *
     * Overrides the clone URL for this repository
     *
     * @access public
     * @param string $cUrl clone url
     */
    public function SetCloneUrl($cUrl)
    {
        $this->cloneUrl = $cUrl;
    }

    /**
     * GetPushUrl
     *
     * Gets the push URL for this repository, if specified
     *
     * @access public
     * @return string push url
     */
    public function GetPushUrl()
    {
        if ($this->pushUrl !== null) return $this->pushUrl;

        $pushurl = GitPHP_Util::AddSlash(\GitPHP\Config::GetInstance()->GetValue('pushurl', ''), false);
        if (!empty($pushurl)) $pushurl .= $this->project;

        return $pushurl;
    }

    /**
     * SetPushUrl
     *
     * Overrides the push URL for this repository
     *
     * @access public
     * @param string $pUrl push url
     */
    public function SetPushUrl($pUrl)
    {
        $this->pushUrl = $pUrl;
    }

    /**
     * GetBugUrl
     *
     * Gets the bug URL for this repository, if specified
     *
     * @access public
     * @return string bug url
     */
    public function GetBugUrl()
    {
        if ($this->bugUrl != null) return $this->bugUrl;

        return \GitPHP\Config::GetInstance()->GetValue('bugurl', '');
    }

    /**
     * SetBugUrl
     *
     * Overrides the bug URL for this repository
     *
     * @access public
     * @param string $bUrl bug url
     */
    public function SetBugUrl($bUrl)
    {
        $this->bugUrl = $bUrl;
    }

    /**
     * GetBugPattern
     *
     * Gets the bug pattern for this repository, if specified
     *
     * @access public
     * @return string bug pattern
     */
    public function GetBugPattern()
    {
        if ($this->bugPattern != null) return $this->bugPattern;

        return \GitPHP\Config::GetInstance()->GetValue('bugpattern', '');
    }

    /**
     * SetBugPattern
     *
     * Overrides the bug pattern for this repository
     *
     * @access public
     * @param string $bPat bug pattern
     */
    public function SetBugPattern($bPat)
    {
        $this->bugPattern = $bPat;
    }

    /**
     * SetNotifyEmail
     *
     * Overrides the notification email for this repository
     *
     * @access public
     * @param string $email
     */
    public function SetNotifyEmail($email)
    {
        $this->notify_email = $email;
    }

    /**
     * GetHeadCommit
     *
     * Gets the head commit for this project
     * Shortcut for getting the tip commit of the HEAD branch
     *
     * @return mixed head commit
     * @throws Exception
     */
    public function GetHeadCommit()
    {
        if (!$this->readHeadRef) $this->ReadHeadCommit();

        return $this->GetCommit($this->head);
    }

    /**
     * ReadHeadCommit
     *
     * Reads the head commit hash
     *
     * @access protected
     */
    public function ReadHeadCommit()
    {
        $this->readHeadRef = true;

        $exe = new GitPHP_GitExe($this);
        $args = array();
        $args[] = '--verify';
        $args[] = 'HEAD';
        $this->head = trim($exe->Execute(GIT_REV_PARSE, $args));
    }

    /**
     * GetCommit
     *
     * Get a commit for this project
     *
     * @param $hash
     * @return GitPHP_Commit
     * @throws Exception
     */
    public function GetCommit($hash)
    {
        if (empty($hash)) {
            return null;
        }

        if ($hash === 'HEAD') {
            return $this->GetHeadCommit();
        }

        if (substr_compare($hash, 'refs/heads/', 0, 11) === 0) {
            $head = $this->GetHead(substr($hash, 11));
            if ($head != null) {
                return $head->GetCommit();
            }
            return null;
        } else if (substr_compare($hash, 'refs/tags/', 0, 10) === 0) {
            $tag = $this->GetTag(substr($hash, 10));
            if ($tag != null) {
                $obj = $tag->GetCommit();
                if ($obj != null) {
                    return $obj;
                }
            }
            return null;
        }

        if (preg_match('/[0-9a-f]{40}/i', $hash)) {
            if (!isset($this->commitCache[$hash])) {
                $cacheKey = 'project|' . $this->project . '|commit|' . $hash;
                $cached = GitPHP_Cache::GetInstance()->Get($cacheKey);
                if ($cached) {
                    $this->commitCache[$hash] = $cached;
                } else {
                    $this->commitCache[$hash] = new GitPHP_Commit($this, $hash);
                }
            }

            return $this->commitCache[$hash];
        }

        if (!$this->readRefs) {
            $this->ReadRefList();
        }

        if (isset($this->heads['refs/heads/' . $hash])) {
            return $this->heads['refs/heads/' . $hash]->GetCommit();
        }

        if (isset($this->tags['refs/tags/' . $hash])) {
            return $this->tags['refs/tags/' . $hash]->GetCommit();
        }

        return null;
    }

    /**
     * CompareProject
     *
     * Compares two projects by project name
     *
     * @access public
     * @static
     * @param mixed $a first project
     * @param mixed $b second project
     * @return integer comparison result
     */
    public static function CompareProject($a, $b)
    {
        $catCmp = strcmp($a->GetCategory(), $b->GetCategory());
        if ($catCmp !== 0) return $catCmp;

        return strcmp($a->GetProject(), $b->GetProject());
    }

    /**
     * CompareDescription
     *
     * Compares two projects by description
     *
     * @access public
     * @static
     * @param mixed $a first project
     * @param mixed $b second project
     * @return integer comparison result
     */
    public static function CompareDescription($a, $b)
    {
        $catCmp = strcmp($a->GetCategory(), $b->GetCategory());
        if ($catCmp !== 0) return $catCmp;

        return strcmp($a->GetDescription(), $b->GetDescription());
    }

    /**
     * CompareOwner
     *
     * Compares two projects by owner
     *
     * @access public
     * @static
     * @param mixed $a first project
     * @param mixed $b second project
     * @return integer comparison result
     */
    public static function CompareOwner($a, $b)
    {
        $catCmp = strcmp($a->GetCategory(), $b->GetCategory());
        if ($catCmp !== 0) return $catCmp;

        return strcmp($a->GetOwner(), $b->GetOwner());
    }

    /**
     * CompareAge
     *
     * Compares two projects by age
     *
     * @access public
     * @static
     * @param mixed $a first project
     * @param mixed $b second project
     * @return integer comparison result
     */
    public static function CompareAge($a, $b)
    {
        $catCmp = strcmp($a->GetCategory(), $b->GetCategory());
        if ($catCmp !== 0) return $catCmp;

        if ($a->GetAge() === $b->GetAge()) return 0;
        return ($a->GetAge() < $b->GetAge() ? -1 : 1);
    }

    public function getHashTags($hash)
    {
        return isset($this->all_hashes[$hash]['tags']) ? $this->all_hashes[$hash]['tags'] : array();
    }

    public function getHashHeads($hash)
    {
        return isset($this->all_hashes[$hash]['heads']) ? $this->all_hashes[$hash]['heads'] : array();
    }

    /**
     * GetRefs
     *
     * Gets the list of refs for the project
     *
     * @access public
     * @param string $type type of refs to get
     * @return array array of refs
     */
    public function GetRefs($type = '')
    {
        if (!$this->readRefs) $this->ReadRefList();

        if ($type == 'tags') {
            return $this->tags;
        } else if ($type == 'heads') {
            return $this->heads;
        }

        return array_merge($this->heads, $this->tags);
    }

    private function ReadHeads($dir)
    {
        $dh = opendir($dir);
        if (!$dh) return;

        while (false !== ($f = readdir($dh))) {
            if ($f[0] == '.') continue;
            $path = $dir . "/" . $f;
            if (is_dir($path)) {
                $this->ReadHeads($path);
            } else {
                $hash = rtrim(file_get_contents($path));
                if (strlen($hash) == 40) {
                    $name = substr($path, 11); // strlen('refs/heads/')
                    try {
                        $this->heads[$path] = new GitPHP_Head($this, $name, $hash);
                    } catch (Exception $e) {
                        // oh yeah baby, ignore all exceptions!
                    }
                    $this->all_hashes[$hash]['heads'][] = $name;
                }
            }
        }

        closedir($dh);
    }

    private function ReadTags($dir)
    {
        $dh = opendir($dir);
        if (!$dh) return;

        while (false !== ($f = readdir($dh))) {
            if ($f[0] == '.') continue;
            $path = $dir . "/" . $f;
            if (is_dir($path)) {
                $this->ReadTags($path);
            } else {
                $hash = rtrim(file_get_contents($path));
                if (strlen($hash) == 40) {
                    $name = substr($path, 10); // strlen('refs/tags/')
                    try {
                        $this->tags[$path] = $this->LoadTag($name, $hash);
                    } catch (Exception $e) {
                        // oh yeah baby, ignore all exceptions (second time)!
                    }

                    $this->all_hashes[$hash]['tags'][] = $name;
                }
            }
        }

        closedir($dh);
    }

    /**
     * ReadRefList
     *
     * Reads the list of refs for this project
     *
     * @access protected
     */
    public function ReadRefList()
    {
        $this->readRefs = true;

        /** @noinspection PhpUnusedLocalVariableInspection need this to count execution time */
        $LogCount = new CountClass(__FUNCTION__);

        if (false) {
            // Only fetch new heads and tags, they reside at refs/heads and refs/tags
            $old_cwd = getcwd();
            chdir($this->getPath());

            $this->ReadHeads('refs/heads');
            $this->ReadTags('refs/tags');

            chdir($old_cwd);
            return;
        }

        $exe = new GitPHP_GitExe($this);
        $args = array();
        $args[] = '--heads';
        $args[] = '--tags';
        $args[] = '--dereference';
        $ret = $exe->Execute(GIT_SHOW_REF, $args);
        unset($exe);

        $lines = explode("\n", $ret);

        foreach ($lines as $line) {
            if (preg_match('/^([0-9a-fA-F]{40}) refs\/(tags|heads)\/([^^]+)(\^{})?$/', $line, $regs)) {
                try {
                    $key = 'refs/' . $regs[2] . '/' . $regs[3];
                    if ($regs[2] == 'tags') {
                        if ((!empty($regs[4])) && ($regs[4] == '^{}')) {
                            $derefCommit = $this->GetCommit($regs[1]);
                            if ($derefCommit && isset($this->tags[$key])) {
                                $this->tags[$key]->SetCommit($derefCommit);
                            }
                        } else if (!isset($this->tags[$key])) {
                            $this->tags[$key] = $this->LoadTag($regs[3], $regs[1]);
                        }
                    } else if ($regs[2] == 'heads') {
                        $this->heads[$key] = new GitPHP_Head($this, $regs[3], $regs[1]);
                    }

                    $this->all_hashes[$regs[1]][$regs[2]][] = $regs[3];
                } catch (Exception $e) {}
            }
        }
    }

    /**
     * GetTags
     *
     * Gets list of tags for this project by age descending
     *
     * @access public
     * @param integer $count number of tags to load
     * @return array array of tags
     */
    public function GetTags($count = 0)
    {
        if (!$this->readRefs) $this->ReadRefList();

        $exe = new GitPHP_GitExe($this);
        $args = array();
        $args[] = '--sort=-creatordate';
        $args[] = '--format="%(refname)"';
        if ($count > 0) {
            $args[] = '--count=' . $count;
        }
        $args[] = '--';
        $args[] = 'refs/tags';
        $ret = $exe->Execute(GIT_FOR_EACH_REF, $args);
        unset($exe);

        $lines = explode("\n", $ret);

        $tags = array();

        foreach ($lines as $ref) {
            if (isset($this->tags[$ref])) {
                /** @var $Tag GitPHP_Tag */
                $Tag = $this->tags[$ref];
                $tags[$Tag->getHash()] = $Tag;
            }
        }

        $this->batchLoadTags($tags);
        return array_values($tags);
    }

    /**
     * GetTag
     *
     * Gets a single tag
     *
     * @access public
     * @param string $tag tag to find
     * @return mixed tag object
     * @throws Exception
     */
    public function GetTag($tag)
    {
        if (empty($tag)) return null;

        $key = 'refs/tags/' . $tag;

        if (!isset($this->tags[$key])) {
            $this->tags[$key] = $this->LoadTag($tag);
        }

        return $this->tags[$key];
    }

    /**
     * LoadTag
     *
     * Attempts to load a cached tag, or creates a new object
     *
     * @param string $tag tag to find
     * @param string $hash
     * @return mixed tag object
     * @throws Exception
     */
    private function LoadTag($tag, $hash = '')
    {
        if (empty($tag)) {
            return null;
        }

        $cacheKey = 'project|' . $this->project . '|tag|' . $tag;
        $cached = GitPHP_Cache::GetInstance()->Get($cacheKey);
        if ($cached) {
            return $cached;
        } else {
            return new GitPHP_Tag($this, $tag, $hash);
        }
    }

    /**
     * GetHeads
     *
     * Gets list of heads for this project by age descending
     *
     * @access public
     * @param integer $count number of tags to load
     * @param string $mask
     * @return GitPHP_Head[]
     */
    public function GetHeads($count = 0, $mask = '')
    {
        if (!$this->readRefs) $this->ReadRefList();

        $exe = new GitPHP_GitExe($this);
        $args = [];
        $args[] = '--sort=-committerdate';
        $args[] = '--format="%(refname)"';
        if ($count > 0) {
            $args[] = '--count=' . $count;
        }
        $args[] = '--';
        $ref_mask = 'refs/heads/';
        $args[] = $ref_mask . $mask;
        $ret = $exe->Execute(GIT_FOR_EACH_REF, $args);
        unset($exe);

        $lines = explode("\n", $ret);

        $heads = [];
        $hashes = [];

        foreach ($lines as $ref) {
            if (isset($this->heads[$ref])) {
                /** @var $Head GitPHP_Head */
                $Head = $this->heads[$ref];
                // one hash <-> many heads
                $hashes[$Head->GetHash()] = true;
                $heads[] = $Head;
            }
        }

        $this->batchLoadHeads(array_keys($hashes), $heads);
        return array_values($heads);
    }

    /**
     * Gets default branch for repository
     * It's the branch where HEAD symbolic-ref is pointing
     *
     * @return string
     */
    public function GetDefaultBranch()
    {
        if (empty($this->default_branch)) {
            $this->default_branch = $this->SymbolicRef("HEAD");
            if (strpos($this->default_branch, "refs/heads/") !== 0) {
                $this->default_branch = 'master';
            } else {
                $this->default_branch = explode("/", $this->default_branch, 3)[2] ?? 'master';
            }
        }
        return $this->default_branch;
    }

    /**
     * Reads symbolic-ref link and returns it's content
     *
     * @param $link
     * @return string
     */
    public function SymbolicRef($link)
    {
        $exe = new GitPHP_GitExe($this);
        $object = trim($exe->Execute("symbolic-ref", [$link, '2>/dev/null']));
        return $object;
    }

    /**
     * GetHead
     *
     * Gets a single head
     *
     * @access public
     * @param string $head head to find
     * @return GitPHP_Head head object
     * @throws Exception
     */
    public function GetHead($head)
    {
        if (empty($head)) return null;

        $key = 'refs/heads/' . $head;

        if (!isset($this->heads[$key])) {
            $this->heads[$key] = new GitPHP_Head($this, $head);
        }

        return $this->heads[$key];
    }

    /**
     * GetLogHash
     *
     * Gets log entries as an array of hashes
     *
     * @access public
     * @param string $hash hash to start the log at
     * @param integer $count number of entries to get
     * @param integer $skip number of entries to skip
     * @param null $hashBase
     * @param array $revListOptions
     * @return array array of hashes
     */
    public function GetLogHash($hash, $count = 50, $skip = 0, $hashBase = null, $revListOptions = [])
    {
        return $this->RevList($hash, $count, $skip, $revListOptions, $hashBase);
    }

    /**
     * Reads multiple objects at once and returns contents and types
     *
     * @param array $hashes   list of object hashes
     * @return array          array(array('contents' => array(hash => contents), 'types' => array(hash => type))
     */
    public function BatchReadData(array $hashes)
    {
        if (!count($hashes)) return array();
        /** @noinspection PhpUnusedLocalVariableInspection */
        $LogCount = new CountClass(__FUNCTION__);

        $outfile = tempnam('/tmp', 'objlist');
        $hashlistfile = tempnam('/tmp', 'objlist');
        file_put_contents($hashlistfile, implode("\n", $hashes));
        $Git = new GitPHP_GitExe($this);
        $Git->Execute(GIT_CAT_FILE, array('--batch', ' < ' . escapeshellarg($hashlistfile), ' > ' . escapeshellarg($outfile)));
        unlink($hashlistfile);
        $fp = fopen($outfile, 'r');
        unlink($outfile);

        $types = $contents = array();
        while (!feof($fp)) {
            $ln = rtrim(fgets($fp));
            if (!$ln) continue;
            list($hash, $type, $n) = explode(" ", rtrim($ln));
            $contents[$hash] = fread($fp, $n);
            $types[$hash] = $type;
        }

        return array('contents' => $contents, 'types' => $types);
    }

    /**
     * Loads data for tags all-at-once (with their commit hashes)
     *
     * @param array $hash_tags array(hash => GitPHP_Tag)
     */
    public function BatchLoadTags(array $hash_tags)
    {
        $commit_hashes = array();

        $result = $this->BatchReadData(array_keys($hash_tags));

        /** @var $Tag GitPHP_Tag */
        foreach ($hash_tags as $hash => $Tag) {
            if (!isset($result['types'][$hash])) continue;
            $Tag->ReadData($result['types'][$hash], $result['contents'][$hash]);
            $commit_hashes[] = $Tag->GetCommit()->GetHash();
        }

        $result = $this->BatchReadData(array_unique($commit_hashes));
        foreach ($hash_tags as $Tag) {
            $Commit = $Tag->GetCommit();
            $commit_hash = $Commit->GetHash();
            if (!isset($result['contents'][$commit_hash])) continue;
            $Commit->ReadData($result['contents'][$commit_hash]);
        }
    }

    /**
     * Loads data for heads all-at-once
     *
     * @param string[] $hashes
     * @param GitPHP_Head[] $heads
     */
    public function BatchLoadHeads(array $hashes, array $heads)
    {
        $result = $this->BatchReadData($hashes);
        /** @var $Head GitPHP_Head */
        foreach ($heads as $Head) {
            $hash = $Head->GetHash();
            if (!isset($result['contents'][$hash])) continue;
            $Head->getCommit()->ReadData($result['contents'][$hash]);
        }
    }

    /**
     * GetLog
     *
     * Gets log entries as an array of commit objects
     *
     * Both tags and commit fetching are batch-fetched and pre-initialized
     *
     * @access public
     * @param string $hash hash to start the log at
     * @param integer $count number of entries to get
     * @param integer $skip number of entries to skip
     * @param string $hashBase
     * @param array $revListOptions
     * @return GitPHP_Commit[]
     * @throws Exception
     */
    public function GetLog($hash, $count = 50, $skip = 0, $hashBase = null, $revListOptions = [])
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $LogCount = new CountClass(__FUNCTION__);
        $log = $this->GetLogHash($hash, $count, $skip, $hashBase, $revListOptions);
        if (!$log) return $log;

        $result = $this->BatchReadData($log);
        $contents = $result['contents'];
        $hash_tags = array();

        /** @var $Commit GitPHP_Commit */
        foreach ($log as $i => $hash) {
            $log[$i] = $Commit = $this->GetCommit($hash);
            if (!isset($contents[$hash])) continue;

            $Commit->ReadData($contents[$hash]);
            foreach ($Commit->GetTags() as $Tag) $hash_tags[$Tag->GetHash()] = $Tag;
        }

        $this->BatchLoadTags($hash_tags);
        return $log;
    }

    /**
     * GetBlob
     *
     * Gets a blob from this project
     *
     * @access public
     * @param string $hash blob hash
     * @return GitPHP_Blob|null
     * @throws Exception
     */
    public function GetBlob($hash)
    {
        if (empty($hash)) return null;

        $cacheKey = 'project|' . $this->project . '|blob|' . $hash;
        $cached = GitPHP_Cache::GetInstance()->Get($cacheKey);
        if ($cached) return $cached;

        return new GitPHP_Blob($this, $hash);
    }

    /**
     * GetTree
     *
     * Gets a tree from this project
     *
     * @access public
     * @param string $hash tree hash
     * @return GitPHP_Tree
     * @throws Exception
     */
    public function GetTree($hash)
    {
        if (empty($hash)) return null;

        $cacheKey = 'project|' . $this->project . '|tree|' . $hash;
        $cached = GitPHP_Cache::GetInstance()->Get($cacheKey);
        if ($cached) return $cached;

        return new GitPHP_Tree($this, $hash);
    }

    /**
     * @param $first_tree
     * @param $second_tree
     * @return string
     */
    public function GetDiffTree($first_tree, $second_tree)
    {
        $exe = new GitPHP_GitExe($this);
        return trim($exe->Execute(GIT_DIFF_TREE, ['-r', escapeshellarg($first_tree), escapeshellarg($second_tree), '2>/dev/null']));
    }

    public function SearchText($text, $branch = 'master')
    {
        $result = '';
        if (!empty($text)) {
            $args = array(
                '-I',
                '-n',
                '--break',
                '--heading',
                '-e',
                escapeshellarg($text),
                escapeshellarg($branch),
            );
            $exe = new GitPHP_GitExe($this);
            $result = $exe->Execute(GIT_GREP, $args);
        }
        return $result;
    }

    /**
     * SearchCommit
     *
     * Gets a list of commits with commit messages matching the given pattern
     *
     * @access public
     * @param string $pattern search pattern
     * @param string $hash hash to start searching from
     * @param integer $count number of results to get
     * @param integer $skip number of results to skip
     * @return array array of matching commits
     * @throws Exception
     */
    public function SearchCommit($pattern, $hash = 'HEAD', $count = 50, $skip = 0)
    {
        if (empty($pattern)) {
            return null;
        }

        $args = array();

        $exe = new GitPHP_GitExe($this);
        if ($exe->CanIgnoreRegexpCase()) $args[] = '--regexp-ignore-case';
        unset($exe);

        $args[] = escapeshellarg("--grep=$pattern");

        $ret = $this->RevList($hash, $count, $skip, $args);
        $len = count($ret);

        for ($i = 0; $i < $len; ++$i) {
            $ret[$i] = $this->GetCommit($ret[$i]);
        }
        return $ret;
    }

    /**
     * Search full hash by abbreviated version. Might be useful to filter user's input
     *
     * @param string $abbreviated_hash
     * @param string $object_type filter object type. See git-rev-parse's documentation for more info
     * @return null|string
     */
    public function GetObjectHash($abbreviated_hash, $object_type = "")
    {
        if (empty($abbreviated_hash)) {
            return null;
        }

        if (empty($object_type)) {
            $object_type = "object";
        }

        $search_for = escapeshellarg("{$abbreviated_hash}^{{$object_type}}");

        $exe = new GitPHP_GitExe($this);
        $hash = $exe->Execute(GIT_REV_PARSE, ['--quiet', '--verify', $search_for, '2>/dev/null']);
        if ($hash) {
            return trim($hash);
        }
        return $hash;
    }

    /**
     * SearchAuthor
     *
     * Gets a list of commits with authors matching the given pattern
     *
     * @access public
     * @param string $pattern search pattern
     * @param string $hash hash to start searching from
     * @param integer $count number of results to get
     * @param integer $skip number of results to skip
     * @return array array of matching commits
     * @throws Exception
     */
    public function SearchAuthor($pattern, $hash = 'HEAD', $count = 50, $skip = 0)
    {
        if (empty($pattern)) {
            return null;
        }

        $args = array();

        $exe = new GitPHP_GitExe($this);
        if ($exe->CanIgnoreRegexpCase()) $args[] = '--regexp-ignore-case';
        unset($exe);

        $args[] = escapeshellarg("--author=$pattern");

        $ret = $this->RevList($hash, $count, $skip, $args);
        $len = count($ret);

        for ($i = 0; $i < $len; ++$i) {
            $ret[$i] = $this->GetCommit($ret[$i]);
        }
        return $ret;
    }

    /**
     * SearchCommitter
     *
     * Gets a list of commits with committers matching the given pattern
     *
     * @access public
     * @param string $pattern search pattern
     * @param string $hash hash to start searching from
     * @param integer $count number of results to get
     * @param integer $skip number of results to skip
     * @return array array of matching commits
     * @throws Exception
     */
    public function SearchCommitter($pattern, $hash = 'HEAD', $count = 50, $skip = 0)
    {
        if (empty($pattern)) {
            return null;
        }

        $args = array();

        $exe = new GitPHP_GitExe($this);
        if ($exe->CanIgnoreRegexpCase()) $args[] = '--regexp-ignore-case';
        unset($exe);

        $args[] = escapeshellarg("--committer=$pattern");

        $ret = $this->RevList($hash, $count, $skip, $args);
        $len = count($ret);

        for ($i = 0; $i < $len; ++$i) {
            $ret[$i] = $this->GetCommit($ret[$i]);
        }
        return $ret;
    }

    /**
     * RevList
     *
     * Common code for using rev-list command
     *
     * @access private
     * @param string $hash hash to list from
     * @param integer $count number of results to get
     * @param integer $skip number of results to skip
     * @param array $args args to give to rev-list
     * @param null $hashBase
     * @return array array of hashes
     */
    private function RevList($hash, $count = 50, $skip = 0, $args = array(), $hashBase = null)
    {
        if ($count < 1) {
            return null;
        }

        $exe = new GitPHP_GitExe($this);

        $canSkip = true;

        if ($skip > 0) $canSkip = $exe->CanSkip();

        $file = $args['file'] ?? '';
        unset($args['file']);

        if ($canSkip) {
            $args[] = '--max-count=' . $count;
            if ($skip > 0) {
                $args[] = '--skip=' . $skip;
            }
        } else {
            $args[] = '--max-count=' . ($count + $skip);
        }

        if ($hashBase) {
            $args[] = $hashBase . '..' . $hash;
        } else {
            $args[] = $hash;
        }

        if (!empty($file)) {
            $args[] = '--';
            $args[] = $file;
        }

        // we don't know how to parse STDERR from rev-list command + we don't need it in most cases
        $args[] = "2>/dev/null";

        $revlist = explode("\n", $exe->Execute(GIT_REV_LIST, $args));

        if (!$revlist[count($revlist) - 1]) {
            /* the last newline creates a null entry */
            array_splice($revlist, -1, 1);
        }

        if (($skip > 0) && (!$exe->CanSkip())) {
            return array_slice($revlist, $skip, $count);
        }

        return $revlist;
    }

    /**
     * GetEpoch
     *
     * Gets this project's epoch
     * (time of last change)
     *
     * @access public
     * @return integer timestamp
     */
    public function GetEpoch()
    {
        if (!$this->epochRead) $this->ReadEpoch();

        return $this->epoch;
    }

    /**
     * GetAge
     *
     * Gets this project's age
     * (time since most recent change)
     *
     * @access public
     * @return integer age
     */
    public function GetAge()
    {
        if (!$this->epochRead) $this->ReadEpoch();

        return time() - $this->epoch;
    }

    /**
     * ReadEpoch
     *
     * Reads this project's epoch
     * (timestamp of most recent change)
     *
     * @access private
     */
    private function ReadEpoch()
    {
        $this->epochRead = true;

        $exe = new GitPHP_GitExe($this);

        $args = array();
        $args[] = '--format="%(committer)"';
        $args[] = '--sort=-committerdate';
        $args[] = '--count=1';
        $args[] = 'refs/heads';

        $epochstr = trim($exe->Execute(GIT_FOR_EACH_REF, $args));

        if (preg_match('/ (\d+) [-+][01]\d\d\d$/', $epochstr, $regs)) {
            $this->epoch = $regs[1];
        }

        unset($exe);
    }

    /**
     * GetNotifyEmail
     *
     * Get the notification email for this repository
     *
     * @access public
     */
    public function GetNotifyEmail()
    {
        return $this->notify_email;
    }

    public function GetBaseBranches($branch)
    {
        $main_branches = array_filter(
            GitPHP\Config::GetInstance()->GetBaseBranchesByCategory($this->GetCategory()),
            function ($branch_name) { return $this->GetHead($branch_name)->Exists(); }
        );

        if (!in_array($this->GetDefaultBranch(), $main_branches)) {
            $main_branches[] = $this->GetDefaultBranch();
        }

        $build_branch_pattern = \GitPHP\Config::GetInstance()->GetValue(\GitPHP\Config::BUILD_BRANCH_PATTERN, '');
        if ($build_branch_pattern && is_string($build_branch_pattern) && preg_match($build_branch_pattern, $branch)) {
            // 'build' branch can contain a lot of other branches inside
            // and it's not a good idea to search for branches with common commits for them
            return $main_branches;
        }

        $branch_rev_list = [];
        foreach ($this->GetRevList([$branch, '^master']) as $hash) $branch_rev_list[$hash] = true; // a bit faster to search

        // weights == count of common commits between ancestor and current branch
        $ancestors_weights = [];
        foreach ($this->GetUnmergedCommitsCache() as $name => $ref_info) {
            $rev_list = $ref_info['commits'] ?? [];
            if ($branch == $name) continue;
            foreach ($rev_list as $hash) {
                if (isset($branch_rev_list[$hash])) {
                    $ancestors_weights[$name] = ($ancestors_weights[$name] ?? 0) + 1;
                }
            }
        }

        $base_branches = array_merge($main_branches, array_keys($ancestors_weights));

        // sort them so that branch with the least diff length will be first
        usort(
            $base_branches,
            function ($first_branch, $second_branch) use ($branch, $ancestors_weights) {
                $weight_diff = ($ancestors_weights[$first_branch] ?? 0) <=> ($ancestors_weights[$second_branch] ?? 0);
                return -$weight_diff;
            }
        );
        return $base_branches;
    }

    /**
     * @param string $first_commit
     * @param string $second_commit
     * @return GitPHP_Commit|null
     * @throws Exception
     */
    public function getMergeBase(string $first_commit, string $second_commit)
    {
        if (!$first_commit || !$second_commit) {
            return null;
        }
        $hash = trim((new GitPHP_GitExe($this))->Execute(GIT_MERGE_BASE, [$first_commit, $second_commit, '2>/dev/null']));
        if (!$hash) {
            return null;
        }
        return new GitPHP_Commit($this, $hash);
    }

    /**
     * If unmerged commits cache is enabled, we will get associative array in form
     * ['branch_name' => ['head' => head, 'commits' => git-rev-list]]
     *
     * we will use this to populate base-branch list with branches
     *
     * If cache is disabled (e.g. not generated), this function will return empty array.
     * In this case there will be only default bases in the list because it's hard
     * to search for unmerged commits on big repository in runtime
     *
     * @see \GitPHP\Config::GetBaseBranchesByCategory()
     *
     * @return array|mixed
     */
    public function GetUnmergedCommitsCache()
    {
        $file = $this->GetPath() . "/.codeisok_cache.php";
        if (!is_file($file)) return [];
        $raw_cache = file_get_contents($this->GetPath() . "/.codeisok_cache.php");
        $cache = json_decode($raw_cache, 1);
        if (!$cache) return [];
        return $cache;
    }

    public function GetRevList($args)
    {
        $args[] = '2>/dev/null';
        $hashes = trim((new GitPHP_GitExe($this))->Execute(GIT_REV_LIST, $args));
        return array_map('trim', explode("\n", $hashes));
    }

    /**
     * Update cache stored on FS
     *
     * @see GitPHP_Project::GetUnmergedCommitsCache()
     */
    public function UpdateUnmergedCommitsCache()
    {
        // load previous cache
        $cache = $this->GetUnmergedCommitsCache();
        if (!isset($cache['HEAD']) || $cache['HEAD'] !== $this->GetDefaultBranch()) {
            $cache = [];
        }

        // note: don't use quotes in --format arguments. They are not needed because of escapeshellarg call inside ForEachRef
        $unmerged_branches = $this->ForEachRef(["--format=%(objectname)\t%(refname)", '--no-merge', $this->GetDefaultBranch(), 'refs/heads/*']);
        $commits_per_branch = [];
        foreach ($unmerged_branches as $line) {
            $line = explode("\t", $line);
            if (count($line) < 2) continue;

            $head = $line[0];
            $branch = explode("/", $line[1], 3)[2] ?? '';
            if (!$head || !$branch) continue;

            $previous_head = $cache[$branch]['head'] ?? '';
            if ($previous_head == $head) {
                $commits_per_branch[$branch] = $cache[$branch];
            } else {
                $commits_per_branch[$branch] = ['head' => $head, 'commits' => $this->GetRevList([$branch, '^' . $this->GetDefaultBranch()])];
            }
        }
        $commits_per_branch['HEAD'] = $this->GetDefaultBranch();

        file_put_contents(
            $this->GetPath() . "/.codeisok_cache.php",
            json_encode($commits_per_branch)
        );
    }

    /**
     * Shorthand for git-for-each-ref call
     * Output information on each ref
     *
     * @param array $args
     * @return array
     */
    public function ForEachRef($args)
    {
        $args = array_map('escapeshellarg', $args);
        $args[] = '2>/dev/null';
        $Exec = new GitPHP_GitExe($this);
        $results = trim($Exec->execute(GIT_FOR_EACH_REF, $args));
        return array_filter(array_map('trim', explode("\n", $results)));
    }
}
