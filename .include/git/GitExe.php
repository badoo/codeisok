<?php

namespace GitPHP\Git;

/**
 * Constants for git commands
 */
define('GIT_CAT_FILE',     'cat-file');
define('GIT_DIFF_TREE',    'diff-tree');
define('GIT_LS_TREE',      'ls-tree');
define('GIT_REV_LIST',     'rev-list');
define('GIT_REV_PARSE',    'rev-parse');
define('GIT_SHOW_REF',     'show-ref');
define('GIT_ARCHIVE',      'archive');
define('GIT_GREP',         'grep');
define('GIT_BLAME',        'blame');
define('GIT_NAME_REV',     'name-rev');
define('GIT_FOR_EACH_REF', 'for-each-ref');
define('GIT_CONFIG',       'config');
define('GIT_DIFF',         'diff');
define('GIT_LOG',          'log');
define('GIT_SHOW',         'show');
define('GIT_MERGE_BASE',   'merge-base');
define('GIT_BRANCH',       'branch');

class GitExe
{
    /**
     * project
     *
     * Stores the project internally
     *
     * @var \GitPHP\Git\Project
     * @access protected
     */
    protected $project;

    /**
     * bin
     *
     * Stores the binary path internally
     *
     * @access protected
     */
    protected $binary;

    /**
     * __construct
     *
     * Constructor
     *
     * @param string $project project to operate on
     */
    public function __construct($project = null)
    {
        $binary = \GitPHP\Config::GetInstance()->GetValue('gitbin');
        if (empty($binary)) {
            $this->binary = \GitPHP\Git\GitExe::DefaultBinary();
        } else {
            $this->binary = $binary;
        }

        $this->SetProject($project);
    }

    /**
     * SetProject
     *
     * Sets the project for this executable
     *
     * @param mixed $project project to set
     */
    public function SetProject($project = null)
    {
        $this->project = $project;
    }

    /**
     * Execute
     *
     * Executes a command
     *
     * @param string $command the command to execute
     * @param array $args arguments
     * @return string result of command
     */
    public function Execute($command, $args)
    {
        $gitDir = '';
        if ($this->project) {
            $gitDir = '--git-dir=' . $this->project->GetPath();
        }
        $args[] = '2>/dev/null';

        $fullCommand = $this->binary . ' ' . $gitDir . ' ' . $command . ' ' . implode(' ', $args);

        \GitPHP\Log::GetInstance()->timerStart();

        $ret = shell_exec($fullCommand);
        \GitPHP\Log::GetInstance()->timerStop('exec', $fullCommand . "\n\n" . (in_array($command, array('cat-file')) ? substr($ret, 0, 100) : $ret));

        return $ret;
    }

    /**
     * GetBinary
     *
     * Gets the binary for this executable
     *
     * @return string binary
     * @access public
     */
    public function GetBinary()
    {
        return $this->binary;
    }

    /**
     * GetVersion
     *
     * Gets the version of the git binary
     *
     * @return string version
     * @access public
     */
    public function GetVersion()
    {
        $versionCommand = $this->binary . ' --version';
        $ret = trim(shell_exec($versionCommand));
        if (preg_match('/^git version ([0-9\.]+)$/i', $ret, $regs)) {
            return $regs[1];
        }
        return '';
    }

    /**
     * CanSkip
     *
     * Tests if this version of git can skip through the revision list
     *
     * @access public
     * @return boolean true if we can skip
     */
    public function CanSkip()
    {
        $version = $this->GetVersion();
        if (!empty($version)) {
            $splitver = explode('.', $version);

            /* Skip only appears in git >= 1.5.0 */
            if (($splitver[0] < 1) || (($splitver[0] == 1) && ($splitver[1] < 5))) {
                return false;
            }
        }

        return true;
    }

    /**
     * CanShowSizeInTree
     *
     * Tests if this version of git can show the size of a blob when listing a tree
     *
     * @access public
     * @return true if we can show sizes
     */
    public function CanShowSizeInTree()
    {
        $version = $this->GetVersion();
        if (!empty($version)) {
            $splitver = explode('.', $version);

            /*
             * ls-tree -l only appears in git 1.5.3
             * (technically 1.5.3-rc0 but i'm not getting that fancy)
             */
            if (($splitver[0] < 1) || (($splitver[0] == 1) && ($splitver[1] < 5)) || (($splitver[0] == 1) && ($splitver[1] == 5) && ($splitver[2] < 3))) {
                return false;
            }
        }

        return true;
    }

    /**
     * CanIgnoreRegexpCase
     *
     * Tests if this version of git has the regexp tuning option to ignore regexp case
     *
     * @access public
     * @return true if we can ignore regexp case
     */
    public function CanIgnoreRegexpCase()
    {
        $version = $this->GetVersion();
        if (!empty($version)) {
            $splitver = explode('.', $version);

            /*
             * regexp-ignore-case only appears in git 1.5.3
             */
            if (($splitver[0] < 1) || (($splitver[0] == 1) && ($splitver[1] < 5)) || (($splitver[0] == 1) && ($splitver[1] == 5) && ($splitver[2] < 3))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valid
     *
     * Tests if this executable is valid
     *
     * @access public
     * @return boolean true if valid
     */
    public function Valid()
    {
        if (empty($this->binary)) return false;

        exec($this->binary . ' --version', $tmp, $code);
        return $code == 0;
    }

    /**
     * DefaultBinary
     *
     * Gets the default binary for the platform
     *
     * @access public
     * @static
     * @return string binary
     */
    public static function DefaultBinary()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // windows

            $arch = php_uname('m');
            if (strpos($arch, '64') !== false) {
                // match x86_64 and x64 (64 bit)
                // C:\Program Files (x86)\Git\bin\git.exe
                return 'C:\\Progra~2\\Git\\bin\\git.exe';
            } else {
                // 32 bit
                // C:\Program Files\Git\bin\git.exe
                return 'C:\\Progra~1\\Git\\bin\\git.exe';
            }
        } else {
            // *nix, just use PATH
            return 'git';
        }
    }
}
