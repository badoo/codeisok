<?php
/**
 * GitPHP Diff Exe
 *
 * Diff executable class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * DiffExe class
 *
 * Class to handle working with the diff executable
 */
class GitPHP_DiffExe
{
    /**
     * binary
     *
     * Stores the binary path internally
     *
     * @access protected
     */
    protected $binary;

    /**
     * unified
     *
     * Stores whether diff creates unified patches
     *
     * @access protected
     */
    protected $unified = true;

    /**
     * showFunction
     *
     * Stores whether to show the function each change is in
     *
     * @access protected
     */
    protected $showFunction = true;

    /**
     * @var bool ignore whitespace diff option
     */
    protected $ignoreWhitespace = false;

    /**
     * __construct
     *
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        $this->binary = \GitPHP\Config::GetInstance()->GetValue('diffbin');
        if (empty($this->binary)) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $this->binary = 'C:\\Progra~1\\Git\\bin\\diff.exe';
            } else {
                $this->binary = 'diff';
            }
        }
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
     * GetUnified
     *
     * Gets whether diff is running in unified mode
     *
     * @access public
     * @return mixed boolean or number of context lines
     */
    public function GetUnified()
    {
        return $this->unified;
    }

    /**
     * SetUnified
     *
     * Sets whether this diff is running in unified mode
     *
     * @access public
     * @param mixed $unified true or false, or number of context lines
     */
    public function SetUnified($unified)
    {
        $this->unified = $unified;
    }

    /**
     * GetShowFunction
     *
     * Gets whether this diff is showing the function
     *
     * @access public
     * @return boolean true if showing function
     */
    public function GetShowFunction()
    {
        return $this->showFunction;
    }

    /**
     * SetShowFunction
     *
     * Sets whether this diff is showing the function
     *
     * @access public
     * @param boolean $show true to show
     */
    public function SetShowFunction($show)
    {
        $this->showFunction = $show;
    }

    /**
     * Execute
     *
     * Runs diff
     *
     * @access public
     * @param string $fromFile source file
     * @param string $fromName source file display name
     * @param string $toFile destination file
     * @param string $toName destination file display name
     * @return string diff output
     */
    public function Execute($fromFile = null, $fromName = null, $toFile = null, $toName = null)
    {
        if (empty($fromFile) && empty($toFile)) {
            return '';
        }

        if (empty($fromFile)) {
            $fromFile = '/dev/null';
        }

        if (empty($toFile)) {
            $toFile = '/dev/null';
        }

        $args = array();
        if ($this->unified) {
            if (is_numeric($this->unified)) {
                $args[] = '-U';
                $args[] = $this->unified;
            } else {
                $args[] = '-u';
            }

            $args[] = '-L';
            if (empty($fromName)) {
                $args[] = '"' . $fromFile . '"';
            } else {
                $args[] = '"' . $fromName . '"';
            }

            $args[] = '-L';
            if (empty($toName)) {
                $args[] = '"' . $toFile . '"';
            } else {
                $args[] = '"' . $toName . '"';
            }
        }
        if ($this->showFunction) {
            $args[] = '-p';
        }
        if ($this->ignoreWhitespace) {
            $args[] = '-w';
        }

        $args[] = $fromFile;
        $args[] = $toFile;

        $command = $this->binary . ' ' . implode(' ', $args);
        \GitPHP\Log::GetInstance()->timerStart();
        $result = shell_exec($command);
        \GitPHP\Log::GetInstance()->timerStop('exec', "$command\n\n$result");
        return $result;
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
        if (empty($this->binary)) {
            return false;
        }

        exec($this->binary . ' --version', $tmp, $code);
        return $code == 0;
    }

    /**
     * Diff
     *
     * Convenience function to run diff with the default settings
     * and immediately discard the object
     *
     * @param string $fromFile source file
     * @param string $fromName source file display name
     * @param string $toFile destination file
     * @param string $toName destination file display name
     * @param bool $context
     * @param bool $ignoreWhitespace
     * @return string diff output
     */
    public static function Diff($fromFile = null, $fromName = null, $toFile = null, $toName = null, $context = true, $ignoreWhitespace = false)
    {
        \GitPHP\Log::GetInstance()->Log(__METHOD__, var_export(func_get_args(), true));
        $obj = new GitPHP_DiffExe();
        $obj->SetUnified($context);
        $obj->setIgnoreWhitespace($ignoreWhitespace);
        $ret = $obj->Execute($fromFile, $fromName, $toFile, $toName);
        return $ret;
    }

    public function setIgnoreWhitespace($ignore = true)
    {
        $this->ignoreWhitespace = $ignore;
    }
}
