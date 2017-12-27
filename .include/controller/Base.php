<?php
namespace GitPHP\Controller;

abstract class Base
{
    /**
     * tpl
     *
     * Smarty instance
     *
     * @access protected
     */
    protected $tpl;

    /**
     * project
     *
     * Current project
     *
     * @var \GitPHP_Project
     * @access protected
     */
    protected $project;

    /**
     * params
     *
     * Parameters
     *
     * @access protected
     */
    protected $params = [];

    /**
     * headers
     *
     * Headers
     *
     * @access protected
     */
    protected $headers = [];

    /**
     * @var \GitPHP_Session
     */
    protected $Session = null;

    /**
     * __construct
     *
     * Constructor
     *
     * @access public
     * @throws \Exception on invalid project
     */
    public function __construct()
    {
        \GitPHP_Log::GetInstance()->timerStart();
        require_once(\GitPHP_Util::AddSlash(\GitPHP_Config::GetInstance()->GetValue('smarty_prefix', 'lib/smarty/libs/')) . 'Smarty.class.php');
        \GitPHP_Log::GetInstance()->timerStop('require Smarty.class.php');
        $this->tpl = new \Smarty;
        $this->tpl->plugins_dir[] = GITPHP_INCLUDEDIR . 'smartyplugins';
        $this->tpl->template_dir = GITPHP_TEMPLATESDIR;

        if (\GitPHP_Config::GetInstance()->GetValue('debug', false)) {
            $this->tpl->error_reporting = E_ALL;
        }

        if (\GitPHP_Config::GetInstance()->GetValue('cache', false)) {
            $this->tpl->caching = 2;
            if (\GitPHP_Config::GetInstance()->HasKey('cachelifetime')) {
                $this->tpl->cache_lifetime = \GitPHP_Config::GetInstance()->GetValue('cachelifetime');
            }

            $servers = \GitPHP_Config::GetInstance()->GetValue('memcache', null);
            if (isset($servers) && is_array($servers) && (count($servers) > 0)) {
                require_once(GITPHP_CACHEDIR . 'Memcache.class.php');
                \GitPHP_Memcache::GetInstance()->AddServers($servers);
                require_once(GITPHP_CACHEDIR . 'memcache_cache_handler.php');
                $this->tpl->cache_handler_func = 'memcache_cache_handler';
            }
        }

        if (isset($_GET['p'])) {
            $this->project = \GitPHP_ProjectList::GetInstance()->GetProject(str_replace(chr(0), '', $_GET['p']));
        }

        if (isset($_GET['s'])) $this->params['search'] = $_GET['s'];
        if (isset($_GET['st'])) $this->params['searchtype'] = $_GET['st'];

        \GitPHP_Log::GetInstance()->timerStart();
        $this->initSession();
        \GitPHP_Log::GetInstance()->timerStop('initSession');
        \GitPHP_Log::GetInstance()->timerStart();
        $this->checkUser();
        \GitPHP_Log::GetInstance()->timerStop('checkUser');

        if (isset($_GET['p']) && !$this->project) {
            throw new \GitPHP_MessageException(sprintf(__('Invalid project %1$s'), $_GET['p']), true);
        }

        /* this is not a part of initialization */
        \GitPHP_Log::GetInstance()->timerStart();
        $this->ReadQuery();
        \GitPHP_Log::GetInstance()->timerStop('ReadQuery');
    }

    /**
     * GetTemplate
     *
     * Gets the template for this controller
     *
     * @access protected
     * @abstract
     * @return string template filename
     */
    protected abstract function GetTemplate();

    /**
     * GetCacheKey
     *
     * Gets the cache key for this controller
     *
     * @access protected
     * @abstract
     * @return string cache key
     */
    protected abstract function GetCacheKey();

    /**
     * GetCacheKeyPrefix
     *
     * Get the prefix for all cache keys
     *
     * @access private
     * @param bool $projectKeys include project-specific key pieces
     * @return string cache key prefix
     */
    protected function GetCacheKeyPrefix($projectKeys = true)
    {
        $cacheKeyPrefix = \GitPHP_Resource::GetLocale();

        $projList = \GitPHP_ProjectList::GetInstance();
        if ($projList) {
            $cacheKeyPrefix .= '|' . sha1(serialize($projList->GetConfig())) . '|' . sha1(serialize($projList->GetSettings()));
            unset($projList);
        }
        if ($this->project && $projectKeys) {
            $cacheKeyPrefix .= '|' . sha1($this->project->GetProject());
        }

        return $cacheKeyPrefix;
    }

    /**
     * GetFullCacheKey
     *
     * Get the full cache key
     *
     * @access protected
     * @return string full cache key
     */
    protected function GetFullCacheKey()
    {
        $cacheKey = $this->GetCacheKeyPrefix();

        $subCacheKey = $this->GetCacheKey();

        if (!empty($subCacheKey)) $cacheKey .= '|' . $subCacheKey;

        return $cacheKey;
    }

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @abstract
     * @access public
     * @param boolean $local true if caller wants the localized action name
     * @return string action name
     */
    public abstract function GetName($local = false);

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @abstract
     * @access protected
     */
    protected abstract function ReadQuery();

    /**
     * SetParam
     *
     * Set a parameter
     *
     * @access protected
     * @param string $key key to set
     * @param mixed $value value to set
     */
    public function SetParam($key, $value)
    {
        if (empty($key)) return;

        if (empty($value)) unset($this->params[$key]);

        $this->params[$key] = $value;
    }

    /**
     * LoadHeaders
     *
     * Loads headers for this template
     *
     * @access protected
     */
    protected function LoadHeaders() {}

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     * @abstract
     */
    protected abstract function LoadData();

    /**
     * LoadCommonData
     *
     * Loads common data used by all templates
     *
     * @access private
     */
    protected function LoadCommonData()
    {
        $stylesheet = \GitPHP_Config::GetInstance()->GetValue('stylesheet', 'gitphpskin.css');
        if ($stylesheet == 'gitphp.css') {
            // backwards compatibility
            $stylesheet = 'gitphpskin.css';
        }
        $this->tpl->assign('stylesheet', $stylesheet);
        $this->tpl->assign('libVersion', filemtime(GITPHP_LIBDIR));
        $this->tpl->assign('cssversion', filemtime(GITPHP_CSSDIR));
        $this->tpl->assign('jsversion', filemtime(GITPHP_JSDIR));

        $this->tpl->assign('javascript', \GitPHP_Config::GetInstance()->GetValue('javascript', true));
        $this->tpl->assign('homelink', \GitPHP_Config::GetInstance()->GetValue('homelink', __('projects')));
        $this->tpl->assign('action', $this->GetName());
        $this->tpl->assign('actionlocal', $this->GetName(true));
        $this->tpl->assign('project', $this->project ?: null);
        $this->tpl->assign('no_user_header', 0);
        $this->tpl->assign('extracss', null);
        $this->tpl->assign('extrascripts', null);

        $this->tpl->assign('extracss_files', null);
        $this->tpl->assign('extrajs_files', null);

        $this->tpl->assign('logmark', null);
        $this->tpl->assign('branch', null);
        $this->tpl->assign('target', null);
        $this->tpl->assign('hashparent', null);
        $this->tpl->assign('searchtype', null);
        $this->tpl->assign('enablebase', null);
        $this->tpl->assign('base_branches', null);
        $this->tpl->assign('branch_name', null);
        $this->tpl->assign('commit', null);
        $this->tpl->assign('logcommit', null);
        $this->tpl->assign('retbranch', null);
        $this->tpl->assign('tree', null);
        $this->tpl->assign('treecommit', null);
        $this->tpl->assign('titlecommit', null);
        $this->tpl->assign('disablelink', null);
        $this->tpl->assign('taglist', null);
        $this->tpl->assign('hasmoreheads', null);
        $this->tpl->assign('hasmoretags', null);
        $this->tpl->assign('hasmorerevs', null);
        $this->tpl->assign('reviews', null);
        $this->tpl->assign('branchdiff', null);
        $this->tpl->assign('ignoreformat', null);
        $this->tpl->assign('ignorewhitespace', null);
        $this->tpl->assign('diffcontext', null);
        $this->tpl->assign('hash_base', null);
        $this->tpl->assign('hash_head', null);
        $this->tpl->assign('currentcategory', null);
        $this->tpl->assign('base_disabled', null);
        $this->tpl->assign('revlist', null);
        $this->tpl->assign('current', null);
        $this->tpl->assign('highlighter_no_ruler', null);
        $this->tpl->assign('highlighter_diff_enabled', null);
        $this->tpl->assign('geshi', null);
        $this->tpl->assign('datatag', null);
        $this->tpl->assign('sexy', null);
        $this->tpl->assign('opened', null);
        $this->tpl->assign('filediff', null);
        $this->tpl->assign('adminarea', 0);

        if (\GitPHP_Config::GetInstance()->GetValue('search', true)) $this->tpl->assign('enablesearch', true);
        if (\GitPHP_Config::GetInstance()->GetValue('filesearch', true)) $this->tpl->assign('filesearch', true);
        $this->tpl->assign('search', isset($this->params['search']) ? $this->params['search'] : null);
        if (isset($this->params['searchtype'])) $this->tpl->assign('searchtype', $this->params['searchtype']);
        $this->tpl->assign('currentlocale', \GitPHP_Resource::GetLocale());
        $this->tpl->assign('supportedlocales', \GitPHP_Resource::SupportedLocales());

        $getvars = explode('&', $_SERVER['QUERY_STRING']);
        $getvarsmapped = [];
        foreach ($getvars as $varstr) {
            $eqpos = strpos($varstr, '=');
            if ($eqpos > 0) {
                $var = substr($varstr, 0, $eqpos);
                $val = substr($varstr, $eqpos + 1);
                if (!(empty($var) || empty($val))) {
                    $getvarsmapped[$var] = urldecode($val);
                }
            }
        }
        $this->tpl->assign('requestvars', $getvarsmapped);

        $this->tpl->assign('snapshotformats', \GitPHP_Archive::SupportedFormats());
        $this->tpl->assign('Session', $this->Session);
        $this->tpl->assign('User', $this->Session->getUser());

        /* header.tpl */
        $this->tpl->assign('user_name', $this->Session->getUser()->getName());
        $this->tpl->assign('is_gitosis_admin', $this->Session->getUser()->isGitosisAdmin());
        $this->tpl->assign('url_gitosis', \GitPHP_Application::getUrl('gitosis'));
        $this->tpl->assign('url_logout', \GitPHP_Application::getUrl('logout'));
        $this->tpl->assign('url_login', \GitPHP_Application::getUrl('login', ['back' => $_SERVER['REQUEST_URI']]));

        $ticketContollers = ['branchdiff', 'branchlog', 'commitdiff', 'commit', 'shortlog', 'log', 'tree'];
        $ticket = in_array($this->GetName(), $ticketContollers) ? $this->guesssTicket() : '';
        $this->tpl->assign('ticket', $ticket);
        $this->tpl->assign('ticket_href', \GitPHP\Tracker::instance()->getTicketUrl($ticket));
        $this->tpl->assign(
            'fixlineheight',
            isset($_COOKIE[\GitPHP_Application::GITPHP_FIX_LINEHEIGHT_COOKIE]) && $_COOKIE[\GitPHP_Application::GITPHP_FIX_LINEHEIGHT_COOKIE]
        );
    }

    /**
     * RenderHeaders
     *
     * Renders any special headers
     *
     * @access public
     */
    public function RenderHeaders()
    {
        $this->LoadHeaders();

        if (count($this->headers) > 0) {
            foreach ($this->headers as $hdr) {
                header($hdr);
            }
        }
    }

    /**
     * Render
     *
     * Renders the output
     *
     * @access public
     */
    public function Render()
    {
        \GitPHP_Log::GetInstance()->timerStart();
        $cache = \GitPHP_Config::GetInstance()->GetValue('cache', false);
        $cacheexpire = \GitPHP_Config::GetInstance()->GetValue('cacheexpire', true);
        if ($cache && ($cacheexpire === true)) {
            $this->CacheExpire();
        }
        \GitPHP_Log::GetInstance()->timerStop(__METHOD__ . ' cache', null);

        if (!$this->tpl->is_cached($this->GetTemplate(), $this->GetFullCacheKey())) {
            \GitPHP_Log::GetInstance()->timerStart();
            $this->LoadCommonData();
            \GitPHP_Log::GetInstance()->timerStop(__METHOD__ . ' LoadCommonData', null);

            \GitPHP_Log::GetInstance()->timerStart();
            $this->LoadData();
            \GitPHP_Log::GetInstance()->timerStop(__METHOD__ . ' LoadData', null);
        }
        \GitPHP_Log::GetInstance()->timerStart();
        $this->tpl->display($this->GetTemplate(), $this->GetFullCacheKey());
        \GitPHP_Log::GetInstance()->timerStop(__METHOD__ . ' display', null);
    }

    /**
     * CacheExpire
     *
     * Expires the cache
     *
     * @access public
     * @param boolean $expireAll expire the whole cache
     */
    public function CacheExpire($expireAll = false)
    {
        if ($expireAll) {
            $this->tpl->clear_all_cache();
            return;
        }

        if (!$this->project) return;

        $epoch = $this->project->GetEpoch();
        if (empty($epoch)) return;

        $age = $this->project->GetAge();

        $this->tpl->clear_cache(null, $this->GetCacheKeyPrefix(), null, $age);
        $this->tpl->clear_cache('projectlist.tpl', $this->GetCacheKeyPrefix(false), null, $age);
    }

    protected function initSession()
    {
        $this->Session = \GitPHP_Session::instance();
    }

    public static function finishScript()
    {
        exit;
    }

    protected function redirect($url, $code = 302)
    {
        if (\GitPHP_Config::GetInstance()->GetValue('debug', false)) {
            echo '<a href="' . $url . '">' . htmlspecialchars($url) . '</a>';

            \GitPHP_Log::GetInstance()->printHtmlHeader();
            \GitPHP_Log::GetInstance()->printHtml();
            \GitPHP_Log::GetInstance()->printHtmlFooter();

            static::finishScript();
        }
        header("Location: " . $url);
        $location = htmlspecialchars($url);
        echo <<<END
<html>
<head>
<title>Status $code - document moved</title>
<meta http-equiv="Refresh" content="0; url=$location">
</head>
<body bgcolor="#ffffff" text="#000000" link="#ff0000" alink="#ff0000" vlink="#ff0000">
Document moved: <a href="$location">$location</a>
</body>
</html>
END;
        static::finishScript();
    }

    protected function checkUser()
    {
        $action = isset($_GET['a']) ? $_GET['a'] : null;
        $skipAuthorization = array_merge(['login'], Git::SUPPORTED_ACTIONS);
        if (!$this->Session->isAuthorized() && !in_array($action, $skipAuthorization)) {
            $this->redirect('/?a=login&back=' . urlencode($_SERVER['REQUEST_URI']));
        }
    }

    protected function guesssTicket()
    {
        $ticket = '';
        $review = isset($_GET['review']) ? (int)$_GET['review'] : 0;
        $hashKeys = ['h', 'hb', 'branch'];
        foreach ($hashKeys as $hashKey) {
            $hash = isset($_GET[$hashKey]) ? $_GET[$hashKey] : null;
            if (preg_match('#^[a-z0-9]+$#', $hash, $matches)) {
                $commit = $this->project->GetCommit($hash);
                if (empty($commit)) continue;
                $comment = $commit->GetComment();
                if (isset($comment[0]) && preg_match('#\[([A-Z]+\-[0-9]+)\]#', $comment[0], $matches)) {
                    $ticket = $matches[1];
                }
            } else if (preg_match('#^([A-Z]+-[0-9]+)#', $hash, $matches)) {
                $ticket = $matches[1];
            } else if (preg_match('#refs/heads/([A-Z]+-[0-9]+)#', $hash, $matches)) {
                $ticket = $matches[1];
            } else if (preg_match(\GitPHP\Tracker::instance()->getTicketRegexp(), $hash, $matches)) {
                $ticket = $matches['ticket'];
            }
        }
        if ($review) {
            $reviewObj = \GitPHP_Db::getInstance()->findReviewById($review);
            $ticket = \GitPHP\Tracker::instance()->parseTicketFromString($reviewObj['ticket']);
        }
        return $ticket;
    }

    /**
     * Get value by key from $_REQUEST array or return $default value
     *
     * @param $key
     * @param null $default
     * @return mixed
     */
    protected function getVar($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
}
