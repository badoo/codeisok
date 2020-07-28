<?php
namespace GitPHP\Controller;

class Blame extends Base
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->project) {
            throw new \GitPHP\MessageException(__('Project is required'), true);
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
        if (isset($this->params['js']) && $this->params['js']) {
            return 'blamedata.tpl';
        }
        return 'blame.tpl';
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
            return __('blame');
        }
        return 'blame';
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
        if (isset($_GET['f'])) $this->params['file'] = $_GET['f'];
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }
        if (isset($_GET['o']) && ($_GET['o'] == 'js')) {
            $this->params['js'] = true;
            \GitPHP\Log::GetInstance()->SetEnabled(false);
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
        $head = $this->project->GetHeadCommit();
        $this->tpl->assign('head', $head);

        $commit = $this->project->GetCommit($this->params['hashbase']);
        $this->tpl->assign('commit', $commit);

        if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
            $this->params['hash'] = $commit->PathToHash($this->params['file']);
        }

        $blob = $this->project->GetBlob($this->params['hash']);
        if ($this->params['file']) $blob->SetPath($this->params['file']);
        $blob->SetCommit($commit);
        $this->tpl->assign('blob', $blob);

        $this->tpl->assign('blame', $blob->GetBlame());

        if (isset($this->params['js']) && $this->params['js']) {
            return;
        }

        $this->tpl->assign('tree', $commit->GetTree());

        if (\GitPHP\Config::GetInstance()->GetValue('geshi', true)) {
            include_once(\GitPHP_Util::AddSlash(\GitPHP\Config::GetInstance()->GetValue('geshiroot', 'lib/geshi/')) . "geshi.php");
            if (class_exists('GeSHi')) {
                $geshi = new \GeSHi("", 'php');
                if ($geshi) {
                    $lang = $geshi->get_language_name_from_extension(substr(strrchr($blob->GetName(), '.'), 1));
                    if (!empty($lang)) {
                        $geshi->enable_classes();
                        $geshi->enable_strict_mode(GESHI_MAYBE);
                        $geshi->set_source($blob->GetData());
                        $geshi->set_language($lang);
                        $geshi->set_header_type(GESHI_HEADER_PRE_TABLE);
                        $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
                        $output = $geshi->parse_code();

                        $bodystart = strpos($output, '<td');
                        $bodyend = strrpos($output, '</tr>');

                        if (($bodystart !== false) && ($bodyend !== false)) {
                            $geshihead = substr($output, 0, $bodystart);
                            $geshifoot = substr($output, $bodyend);
                            $geshibody = substr($output, $bodystart, $bodyend);

                            $this->tpl->assign('geshihead', $geshihead);
                            $this->tpl->assign('geshibody', $geshibody);
                            $this->tpl->assign('geshifoot', $geshifoot);
                            $this->tpl->assign('extracss', $geshi->get_stylesheet());
                            $this->tpl->assign('geshi', true);
                        }
                    }
                }
            }
        }
    }
}
