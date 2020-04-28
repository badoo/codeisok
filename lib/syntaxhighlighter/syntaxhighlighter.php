<?php

class SyntaxHighlighter
{
    const TYPE_TEXT         = 'text';
    const TYPE_PHP          = 'php';
    const TYPE_JS           = 'js';
    const TYPE_CSS          = 'css';
    const TYPE_BASH         = 'bash';
    const TYPE_JAVA         = 'java';
    const TYPE_XML          = 'xml';
    const TYPE_SQL          = 'sql';
    const TYPE_PYTHON       = 'python';
    const TYPE_DIFF         = 'diff';
    const TYPE_CPP          = 'cpp';
    const TYPE_APPLE_SCRIPT = 'applescript';
    const TYPE_RUBY         = 'ruby';
    const TYPE_CSHARP       = 'csharp';
    const TYPE_OBJC         = 'objc';
    const TYPE_PROTOBUF     = 'protobuf';
    const TYPE_RST          = 'rst';
    const TYPE_GO           = 'go';
    const TYPE_YAML         = 'yaml';
    const TYPE_KOTLIN       = 'kotlin';

    const BASE_PATH  = 'lib/syntaxhighlighter/';

    protected static $ext_map = array(
        'php'   => self::TYPE_PHP,
        'phtml' => self::TYPE_PHP,
        'inc'   => self::TYPE_PHP,
        'js'    => self::TYPE_JS,
        'jsx'    => self::TYPE_JS,
        'ts'    => self::TYPE_JS,
        'tsx'    => self::TYPE_JS,
        'tpl'   => self::TYPE_XML,
        'html'  => self::TYPE_XML,
        'xml'   => self::TYPE_XML,
        'svg'   => self::TYPE_XML,
        'css'   => self::TYPE_CSS,
        'sh'    => self::TYPE_BASH,
        'sql'   => self::TYPE_SQL,
        'java'  => self::TYPE_JAVA,
        'cfg'  => self::TYPE_JAVA,
        'stg'  => self::TYPE_JAVA,
        'diff'  => self::TYPE_DIFF,
        'c'  => self::TYPE_CPP,
        'h'  => self::TYPE_CPP,
        'cpp'  => self::TYPE_CPP,
        'hpp'  => self::TYPE_CPP,
        'am'  => self::TYPE_BASH,
        'ac'  => self::TYPE_BASH,
        'm4'  => self::TYPE_BASH,
        'py'  => self::TYPE_PYTHON,
        'm'  => self::TYPE_OBJC,
        'swift'  => self::TYPE_OBJC,
        'ipynb'  => self::TYPE_JS,
        'json'  => self::TYPE_JS,
        'mm'  => self::TYPE_CPP,
        'plist'  => self::TYPE_XML,
        'command'  => self::TYPE_BASH,
        'xcscheme' => self::TYPE_XML,
        'project' => self::TYPE_XML,
        'classpath' => self::TYPE_XML,
        'pch' => self::TYPE_CPP,
        'rb' => self::TYPE_RUBY,
        'sln' => self::TYPE_RUBY,
        'StyleCop' => self::TYPE_XML,
        'csproj' => self::TYPE_XML,
        'cs' => self::TYPE_CSHARP,
        'proto' => self::TYPE_PROTOBUF,
        'cmd' => self::TYPE_BASH,
        'config' => self::TYPE_XML,
        'xaml' => self::TYPE_XML,
        'resx' => self::TYPE_XML,
        'nuspec' => self::TYPE_XML,
        'go' => self::TYPE_GO,
        'rst' => self::TYPE_RST,
        'yml' => self::TYPE_YAML,
        'yaml' => self::TYPE_YAML,
        'kt' => self::TYPE_KOTLIN,
    );

    protected static $brush_map = array(
        self::TYPE_TEXT             => 'shBrushPlain.js',
        self::TYPE_PHP              => 'shBrushPhp.js',
        self::TYPE_JS               => 'shBrushJScript.js',
        self::TYPE_CSS              => 'shBrushCss.js',
        self::TYPE_BASH             => 'shBrushBash.js',
        self::TYPE_JAVA             => 'shBrushJava.js',
        self::TYPE_XML              => 'shBrushXml.js',
        self::TYPE_SQL              => 'shBrushSql.js',
        self::TYPE_PYTHON           => 'shBrushPython.js',
        self::TYPE_DIFF             => 'shBrushDiff.js',
        self::TYPE_CPP              => 'shBrushCpp.js',
        self::TYPE_APPLE_SCRIPT     => 'shBrushAppleScript.js',
        self::TYPE_RUBY             => 'shBrushRuby.js',
        self::TYPE_CSHARP           => 'shBrushCSharp.js',
        self::TYPE_OBJC             => 'shBrushObjC.js',
        self::TYPE_PROTOBUF         => 'shBrushProtobuf.js',
        self::TYPE_RST              => 'shBrushRst.js',
        self::TYPE_GO               => 'shBrushGolang.js',
        self::TYPE_YAML             => 'shBrushYaml.js',
        self::TYPE_KOTLIN           => 'shBrushKotlin.js',
    );

    protected $filename;
    protected $extension;

    public static function getTypeByExtension($extension)
    {
        return isset(self::$ext_map[$extension]) ? self::$ext_map[$extension] : false;
    }

    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->extension = substr(strrchr($this->filename,'.'),1);
        if (!$this->extension) { //for special filenames
            if (in_array($this->filename, array('SConscript', 'SConstruct'))) {
                $this->extension = 'py';
            }
        }
        $this->type = self::getTypeByExtension($this->extension);
        if (!$this->type) {
            $this->type = self::TYPE_TEXT;
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function getCssList()
    {
        $path = self::BASE_PATH . 'styles/';
        return array(
            $path . 'shCore.css',
            $path . 'shThemeDefault.css',
        );
    }

    public function getJsList()
    {
        $path = self::BASE_PATH . 'scripts/';
        return array(
            $path . 'XRegExp.js',
            $path . 'shCore.js?1',
            $path . 'shAutoloader.js',
        );
    }

    public function getBrushesList()
    {
        $path = self::BASE_PATH . 'scripts/';
        return array(
            $this->type => $path . self::$brush_map[$this->type],
        );
    }

    public function getBrushName()
    {
        return $this->type;
    }
}
