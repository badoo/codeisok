<?php

namespace GitPHP\Git;

class Archive
{
    const GITPHP_COMPRESS_TAR = 'tar';
    const GITPHP_COMPRESS_BZ2 = 'tbz2';
    const GITPHP_COMPRESS_GZ = 'tgz';
    const GITPHP_COMPRESS_ZIP = 'zip';

    /**
     * Stores the object for this archive internally
     * @var \GitPHP_GitObject
     */
    protected $gitObject = null;

    /**
     * Stores the project for this archive internally
     */
    protected $project = null;

    /**
     * Stores the archive format internally
     */
    protected $format;

    /**
     * Stores the archive filename internally
     */
    protected $fileName = '';

    /**
     * Stores the archive path internally
     */
    protected $path = '';

    /**
     * Stores the archive prefix internally
     */
    protected $prefix = '';

    /**
     * __construct
     *
     * Instantiates object
     *
     * @param $project
     * @param mixed $gitObject the object
     * @param string $format the format for the archive
     * @param string $path
     * @param string $prefix
     * @throws \Exception
     */
    public function __construct($project, $gitObject, $format = self::GITPHP_COMPRESS_ZIP, $path = '', $prefix = '')
    {
        $this->SetProject($project);
        $this->SetObject($gitObject);
        $this->SetFormat($format);
        $this->SetPath($path);
        $this->SetPrefix($prefix);
    }

    /**
     * GetFormat
     *
     * Gets the archive format
     *
     * @access public
     * @return integer archive format
     */
    public function GetFormat()
    {
        return $this->format;
    }

    /**
     * SetFormat
     *
     * Sets the archive format
     *
     * @param integer $format archive format
     */
    public function SetFormat($format)
    {
        if ((($format == self::GITPHP_COMPRESS_BZ2) && (!function_exists('bzcompress')))
            || (($format == self::GITPHP_COMPRESS_GZ) && (!function_exists('gzencode')))) {
            /*
             * Trying to set a format but doesn't have the appropriate
             * compression function, fall back to tar
             */
            $format = self::GITPHP_COMPRESS_TAR;
        }

        $this->format = $format;
    }

    /**
     * GetObject
     *
     * Gets the object for this archive
     *
     * @return mixed the git object
     */
    public function GetObject()
    {
        return $this->gitObject;
    }

    /**
     * SetObject
     *
     * Sets the object for this archive
     *
     * @param mixed $object the git object
     * @throws \Exception
     */
    public function SetObject($object)
    {
        // Archive only works for commits and trees
        if (($object != null) && (!(($object instanceof \GitPHP_Commit) || ($object instanceof \GitPHP_Tree)))) {
            throw new \Exception('Invalid source object for archive');
        }

        $this->gitObject = $object;
    }

    /**
     * GetProject
     *
     * Gets the project for this archive
     *
     * @return mixed the project
     */
    public function GetProject()
    {
        if ($this->project) return $this->project;

        if ($this->gitObject) return $this->gitObject->GetProject();

        return null;
    }

    /**
     * SetProject
     *
     * Sets the project for this archive
     *
     * @param mixed $project the project
     */
    public function SetProject($project)
    {
        $this->project = $project;
    }

    /**
     * GetExtension
     *
     * Gets the extension to use for this archive
     *
     * @return string extension for the archive
     * @throws \Exception
     */
    public function GetExtension()
    {
        return \GitPHP\Git\Archive::FormatToExtension($this->format);
    }

    /**
     * GetFilename
     *
     * Gets the filename for this archive
     *
     * @return string filename
     * @throws \Exception
     */
    public function GetFilename()
    {
        if (!empty($this->fileName)) {
            return $this->fileName;
        }

        $fname = $this->GetProject()->GetSlug();

        $fname .= '.' . $this->GetExtension();

        return $fname;
    }

    /**
     * SetFilename
     *
     * Sets the filename for this archive
     *
     * @param string $name filename
     */
    public function SetFilename($name = '')
    {
        $this->fileName = $name;
    }

    /**
     * GetPath
     *
     * Gets the path to restrict this archive to
     *
     * @return string path
     */
    public function GetPath()
    {
        return $this->path;
    }

    /**
     * SetPath
     *
     * Sets the path to restrict this archive to
     *
     * @param string $path path to restrict
     */
    public function SetPath($path = '')
    {
        $this->path = $path;
    }

    /**
     * GetPrefix
     *
     * Gets the directory prefix to use for files in this archive
     *
     * @return string prefix
     */
    public function GetPrefix()
    {
        if (!empty($this->prefix)) {
            return $this->prefix;
        }

        return $this->GetProject()->GetSlug() . '/';
    }

    /**
     * SetPrefix
     *
     * Sets the directory prefix to use for files in this archive
     *
     * @param string $prefix prefix to use
     */
    public function SetPrefix($prefix = '')
    {
        if (empty($prefix)) {
            $this->prefix = $prefix;
            return;
        }

        if (substr($prefix, -1) != '/') {
            $prefix .= '/';
        }

        $this->prefix = $prefix;
    }

    /**
     * GetHeaders
     *
     * Gets the headers to send to the browser for this file
     *
     * @return array header strings
     * @throws \Exception
     */
    public function GetHeaders()
    {
        $headers = array();

        switch ($this->format) {
            case self::GITPHP_COMPRESS_TAR:
                $headers[] = 'Content-Type: application/x-tar';
                break;

            case self::GITPHP_COMPRESS_BZ2:
                $headers[] = 'Content-Type: application/x-bzip2';
                break;

            case self::GITPHP_COMPRESS_GZ:
                $headers[] = 'Content-Type: application/x-gzip';
                break;

            case self::GITPHP_COMPRESS_ZIP:
                $headers[] = 'Content-Type: application/x-zip';
                break;

            default:
                throw new \Exception('Unknown compression type');
        }

        if (count($headers) > 0) {
            $headers[] = 'Content-Disposition: attachment; filename=' . $this->GetFilename();
        }

        return $headers;
    }

    /**
     * GetData
     *
     * Gets the archive data
     *
     * @return string archive data
     * @throws \Exception
     */
    public function GetData()
    {
        if (!$this->gitObject) {
            throw new \Exception('Invalid object for archive');
        }

        $exe = new \GitPHP_GitExe($this->GetProject());

        $args = array();

        switch ($this->format) {
            case self::GITPHP_COMPRESS_ZIP:
                $args[] = '--format=zip';
                break;

            case self::GITPHP_COMPRESS_TAR:
            case self::GITPHP_COMPRESS_BZ2:
            case self::GITPHP_COMPRESS_GZ:
                $args[] = '--format=tar';
                break;
        }

        $args[] = '--prefix=' . $this->GetPrefix();
        $args[] = $this->gitObject->GetHash();

        if (!empty($this->path)) $args[] = $this->path;

        $data = $exe->Execute(GIT_ARCHIVE, $args);
        unset($exe);

        switch ($this->format) {
            case self::GITPHP_COMPRESS_BZ2:
                $data = bzcompress($data, \GitPHP\Config::GetInstance()->GetValue('compresslevel', 4));
                break;

            case self::GITPHP_COMPRESS_GZ:
                $data = gzencode($data, \GitPHP\Config::GetInstance()->GetValue('compresslevel', -1));
                break;
        }

        return $data;
    }

    /**
     * FormatToExtension
     *
     * Gets the extension to use for a particular format
     *
     * @param string $format format to get extension for
     * @return string file extension
     * @throws \Exception
     */
    public static function FormatToExtension($format)
    {
        switch ($format) {
            case self::GITPHP_COMPRESS_TAR:
                return 'tar';
                break;

            case self::GITPHP_COMPRESS_BZ2:
                return 'tar.bz2';
                break;

            case self::GITPHP_COMPRESS_GZ:
                return 'tar.gz';
                break;

            case self::GITPHP_COMPRESS_ZIP:
                return 'zip';
                break;

            default:
                throw new \Exception('Unknown compression type');
        }
    }

    /**
     * SupportedFormats
     *
     * Gets the supported formats for the archiver
     *
     * @access public
     * @static
     * @return array array of formats mapped to extensions
     * @throws \Exception
     */
    public static function SupportedFormats()
    {
        $formats = array();
        $formats[self::GITPHP_COMPRESS_TAR] = \GitPHP\Git\Archive::FormatToExtension(self::GITPHP_COMPRESS_TAR);
        $formats[self::GITPHP_COMPRESS_ZIP] = \GitPHP\Git\Archive::FormatToExtension(self::GITPHP_COMPRESS_ZIP);

        if (function_exists('bzcompress')) {
            $formats[self::GITPHP_COMPRESS_BZ2] = \GitPHP\Git\Archive::FormatToExtension(self::GITPHP_COMPRESS_BZ2);
        }
        if (function_exists('gzencode')) {
            $formats[self::GITPHP_COMPRESS_GZ] = \GitPHP\Git\Archive::FormatToExtension(self::GITPHP_COMPRESS_GZ);
        }

        return $formats;
    }
}
