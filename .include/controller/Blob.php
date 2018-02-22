<?php
namespace GitPHP\Controller;

require_once __DIR__ . '/../../lib/syntaxhighlighter/syntaxhighlighter.php';

/**
 * Blob controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class Blob extends Base
{
    protected $code_mirror_modes = [
        // 'file_extension' => 'mode' | 'mime-type'
        \SyntaxHighlighter::TYPE_TEXT => 'text',
        \SyntaxHighlighter::TYPE_PHP => 'php',
        \SyntaxHighlighter::TYPE_JS => 'javascript',
        \SyntaxHighlighter::TYPE_CSS => 'css',
        \SyntaxHighlighter::TYPE_BASH => 'shell',
        \SyntaxHighlighter::TYPE_JAVA => 'text/x-java',
        \SyntaxHighlighter::TYPE_XML => 'xml',
        \SyntaxHighlighter::TYPE_SQL => 'sql',
        \SyntaxHighlighter::TYPE_PYTHON => 'python',
        \SyntaxHighlighter::TYPE_DIFF => 'diff',
        \SyntaxHighlighter::TYPE_CPP => 'text/x-c++src',
        // there is no applescript mode in https://github.com/marijnh/CodeMirror/tree/master/mode :(
        // \SyntaxHighlighter::TYPE_APPLE_SCRIPT => 'applescript',
        \SyntaxHighlighter::TYPE_RUBY => 'ruby',
        \SyntaxHighlighter::TYPE_CSHARP => 'text/x-csharp',
        \SyntaxHighlighter::TYPE_OBJC => 'text/x-objc',
        \SyntaxHighlighter::TYPE_KOTLIN => 'text/x-kotlin',
    ];

    public function __construct()
    {
        parent::__construct();
        if (!$this->project) {
            throw new \GitPHP_MessageException(__('Project is required'), true);
        }
    }

    /**
     * GetTemplate
     *
     * Gets the template for this controller
     *
     * @access protected
     * @return string template filename
     */
    protected function GetTemplate()
    {
        if (isset($this->params['plain']) && $this->params['plain']) return 'blobplain.tpl';
        return 'blob.tpl';
    }

    /**
     * GetCacheKey
     *
     * Gets the cache key for this controller
     *
     * @access protected
     * @return string cache key
     */
    protected function GetCacheKey()
    {
        return (isset($this->params['hashbase']) ? $this->params['hashbase'] : '') . '|' . (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '');
    }

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @access public
     * @param boolean $local true if caller wants the localized action name
     * @return string action name
     */
    public function GetName($local = false)
    {
        if ($local) {
            return __('blob');
        }
        return 'blob';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    protected function ReadQuery()
    {
        if (isset($_GET['hb'])) $this->params['hashbase'] = $_GET['hb'];
        else $this->params['hashbase'] = 'HEAD';

        $this->params['file'] = isset($_GET['f']) ? $_GET['f'] : null;
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }
        $this->readHighlighter();
        $this->params['hi'] = 'sexy';
        if (isset($_GET['hi']) && in_array($_GET['hi'], array('no', 'geshi', 'php', 'sexy'))) {
            $this->params['hi'] = $_GET['hi'];
        }
        if ($this->params['hi'] === 'geshi' && !$this->checkGeshi()) {
            $this->params['hi'] = 'no';
        }
    }

    protected function readHighlighter()
    {
        $hi = 'geshi';
        if (isset($_GET['hi']) && in_array($_GET['hi'], array('no', 'geshi', 'php', 'sexy'))) {
            $hi = $_GET['hi'];
        }
        if ($hi === 'geshi' && !$this->checkGeshi()) {
            $hi = 'no';
        }
        $this->params['hi'] = $hi;
    }

    protected function checkGeshi()
    {
        $result = false;
        if (\GitPHP_Config::GetInstance()->GetValue('geshi', true)) {
            include_once(\GitPHP_Util::AddSlash(\GitPHP_Config::GetInstance()->GetValue('geshiroot', 'lib/geshi/')) . "geshi.php");
            if (class_exists('GeSHi')) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * LoadHeaders
     *
     * Loads headers for this template
     *
     * @access protected
     */
    protected function LoadHeaders()
    {
        if (isset($this->params['plain']) && $this->params['plain']) {
            \GitPHP_Log::GetInstance()->SetEnabled(false);

    		// XXX: Nasty hack to cache headers
            if (!$this->tpl->is_cached('blobheaders.tpl', $this->GetFullCacheKey())) {
                if (isset($this->params['file'])) $saveas = $this->params['file'];
                else $saveas = $this->params['hash'] . ".txt";

                $headers = array();

                $mime = null;
                if (\GitPHP_Config::GetInstance()->GetValue('filemimetype', true)) {
                    if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
                        $commit = $this->project->GetCommit($this->params['hashbase']);
                        $this->params['hash'] = $commit->PathToHash($this->params['file']);
                    }

                    $blob = $this->project->GetBlob($this->params['hash']);
                    $blob->SetPath($this->params['file']);

                    $mime = $blob->FileMime();
                }

                $file_name = $this->params['file'];
                if (preg_match('/.*?\.(\w+)$/', $file_name, $matches)) {
                    $type = \SyntaxHighlighter::getTypeByExtension($matches[1]);
                    if ($type && isset($this->code_mirror_modes[$type])) {
                        $headers[] = 'Cm-mode: ' . $this->code_mirror_modes[$type];
                    } else {
                        $headers[] = 'Cm-mode: clike';
                    }
                }

                if ($mime && strpos($mime, 'text') !== 0) $headers[] = "Content-type: " . $mime;
                else $headers[] = "Content-type: text/plain; charset=UTF-8";

                $headers[] = "Content-disposition: inline; filename=\"" . $saveas . "\"";

                $this->tpl->assign("blobheaders", serialize($headers));
            }
            $out = $this->tpl->fetch('blobheaders.tpl', $this->GetFullCacheKey());

            $this->headers = unserialize($out);
        }
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    protected function LoadData()
    {
        $commit = $this->project->GetCommit($this->params['hashbase']);
        $this->tpl->assign('commit', $commit);

        if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
            $this->params['hash'] = $commit->PathToHash($this->params['file']);
        }

        $blob = $this->project->GetBlob($this->params['hash']);
        if ($this->params['file']) $blob->SetPath($this->params['file']);
        $blob->SetCommit($commit);
        $this->tpl->assign('blob', $blob);

        if (isset($this->params['plain']) && $this->params['plain']) {
            return;
        }

        $head = $this->project->GetHeadCommit();
        $this->tpl->assign('head', $head);

        $this->tpl->assign('tree', $commit->GetTree());

        if (\GitPHP_Config::GetInstance()->GetValue('filemimetype', true)) {
            $mime = $blob->FileMime();
            if ($mime) {
                $mimetype = strtok($mime, '/');
                if ($mimetype == 'image') {
                    $this->tpl->assign('datatag', true);
                    $this->tpl->assign('mime', $mime);
                    $this->tpl->assign('data', base64_encode($blob->GetData()));
                    return;
                }
            }
        }

        $this->tpl->assign('extrascripts', array('blame'));

        switch ($this->params['hi']) {
            case 'sexy':
                include_once('lib/syntaxhighlighter/syntaxhighlighter.php');
                $SH = new \SyntaxHighlighter($blob->GetName());
                $this->tpl->assign('sexy', 1);
                $this->tpl->assign('extracss_files', $SH->getCssList());
                $this->tpl->assign('extrajs_files', $SH->getJsList());
                $this->tpl->assign('highlighter_brushes', $SH->getBrushesList());
                $this->tpl->assign('highlighter_brush_name', $SH->getBrushName());
                $this->tpl->assign('blobstr', '');
                if (strpos($blob->FileMime(), 'text') !== false || strpos($blob->FileMime(), 'xml') !== false) {
                    $this->tpl->assign('blobstr', $blob->getData(false));
                }
                return;

            case 'php':
                $this->tpl->assign('blobstr', highlight_string($blob->GetData(false), 1));
                $this->tpl->assign('php', 1);
                return;

            case 'geshi':
                $geshi = new \GeSHi("", 'php');
                $lang = $geshi->get_language_name_from_extension(substr(strrchr($blob->GetName(), '.'), 1));
                if (!empty($lang)) {
                    $geshi->enable_classes();
                    $geshi->enable_strict_mode(GESHI_MAYBE);
                    $geshi->set_source($blob->GetData());
                    $geshi->set_language($lang);
                    $geshi->set_header_type(GESHI_HEADER_PRE_TABLE);
                    $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
                    $geshi->set_overall_id('blobData');
                    $this->tpl->assign('geshiout', $geshi->parse_code());
                    $this->tpl->assign('extracss', $geshi->get_stylesheet());
                    $this->tpl->assign('geshi', true);
                    return;
                }

            case 'no':
            default:
                $this->tpl->assign('bloblines', $blob->GetData(true));
        }

        $this->tpl->assign('blobstr', highlight_string($blob->GetData(false), 1));
        $this->tpl->assign('new', isset($_GET['new']));
    }
}
