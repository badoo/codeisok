<?php
class GitPHP_Application
{
    const GITPHP_LOCALE_COOKIE = 'GitPHPLocale';
    const GITPHP_LOCALE_COOKIE_LIFETIME = 31536000; //60 * 60 * 24 * 365 = 1 year
    const GITPHP_FIX_LINEHEIGHT_COOKIE = 'GitPHP_fix_lineheight';
    const GITPHP_FIX_LINEHEIGHT_LIFETIME = 31536000; //60 * 60 * 24 * 365 = 1 year

    public function init()
    {
        set_error_handler(['GitPHP\\Error', 'errorHandler']);
        date_default_timezone_set('UTC');
        error_reporting(E_ALL);

        register_shutdown_function(
            function () {
                $err = error_get_last();
                if ($err) error_log(var_export($err, true));
            }
        );

        try {
            $this->initDebug();
            $this->initResource();
            $this->initConfiguration();
            $this->initProject();
        } catch (\Exception $e) {
            $this->showExceptionMessage($e);
        }
    }

    /**
     * Set the locale based on the user's preference
     */
    private function initResource()
    {
        if (!empty($_GET['l'])) {
            setcookie(self::GITPHP_LOCALE_COOKIE, $_GET['l'], time() + self::GITPHP_LOCALE_COOKIE_LIFETIME);
            GitPHP_Resource::Instantiate($_GET['l']);
        } else if (!empty($_COOKIE[self::GITPHP_LOCALE_COOKIE])) {
            GitPHP_Resource::Instantiate($_COOKIE[self::GITPHP_LOCALE_COOKIE]);
        } else {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                if ($preferredLocale = GitPHP_Resource::FindPreferredLocale($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                    setcookie(self::GITPHP_LOCALE_COOKIE, $preferredLocale, time() + self::GITPHP_LOCALE_COOKIE_LIFETIME);
                    GitPHP_Resource::Instantiate($preferredLocale);
                }
            }
            if (!GitPHP_Resource::Instantiated()) {
                setcookie(self::GITPHP_LOCALE_COOKIE, 0, time() + self::GITPHP_LOCALE_COOKIE_LIFETIME);
            }
        }

        if (!GitPHP_Resource::Instantiated()) {
            GitPHP_Resource::Instantiate(GitPHP_Config::GetInstance()->GetValue('locale', 'en_US'));
        }

        if (isset($_GET['fix_lineheight'])) {
            setcookie(self::GITPHP_FIX_LINEHEIGHT_COOKIE, $_GET['fix_lineheight'], time() + self::GITPHP_FIX_LINEHEIGHT_LIFETIME);
            $_COOKIE[self::GITPHP_FIX_LINEHEIGHT_COOKIE] = $_GET['fix_lineheight'];
        }
    }

    public function initDebug()
    {
        if (GitPHP_Config::DEBUG_ENABLED) {
            if (isset($_GET['debug_mode'])) {
                setcookie('debug_mode', $_GET['debug_mode'], (int)$_GET['debug_mode'] == 0 ? time() - 3600 : null);
                $_COOKIE['debug_mode'] = $_GET['debug_mode'];
            }
            if (isset($_GET['debug_js'])) {
                setcookie('debug_js', $_GET['debug_js'], (int)$_GET['debug_js'] == 0 ? time() - 3600 : null);
                $_COOKIE['debug_js'] = $_GET['debug_js'];
            }
            if (isset($_COOKIE['debug_mode']) && (int)$_COOKIE['debug_mode'] == 1) {
                GitPHP_Config::GetInstance()->SetValue('debug', (bool)(int)$_REQUEST['debug_mode']);
                GitPHP_Log::GetInstance()->SetEnabled((bool)(int)$_REQUEST['debug_mode']);
            }
            if (GitPHP_Config::GetInstance()->GetValue('debug', false)) {
                ini_set('display_errors', 1);
            } else {
                ini_set('display_errors', 0);
            }
        }
    }

    private function initConfiguration()
    {
        $config = GITPHP_CONFIGDIR . 'gitphp.conf.php';
        $tmpConfig = GITPHP_CONFIGDIR . 'gitphp.conf.' . $_SERVER['SERVER_NAME'] . '.php';
        if (isset($_SERVER['SERVER_NAME']) && file_exists($tmpConfig)) {
            $config = $tmpConfig;
        }
        GitPHP_Config::GetInstance()->LoadConfig($config);
    }

    private function initProject()
    {
        if (!GitPHP_Config::GetInstance()->GetValue('projectroot', null)) {
            throw new GitPHP_MessageException(__('A projectroot must be set in the config'), true, 500);
        }

        $exe = new GitPHP_GitExe(null);
        if (!$exe->Valid()) {
            throw new GitPHP_MessageException(sprintf(
                __('Could not run the git executable "%1$s".  You may need to set the "%2$s" config value.'),
                $exe->GetBinary(),
                'gitbin'
            ), true, 500);
        }
        $exe = new GitPHP_DiffExe();
        if (!$exe->Valid()) {
            throw new GitPHP_MessageException(sprintf(
                __('Could not run the diff executable "%1$s".  You may need to set the "%2$s" config value.'),
                $exe->GetBinary(),
                'diffbin'
            ), true, 500);
        }

        if (file_exists(GITPHP_CONFIGDIR . 'projects.conf.php')) {
            GitPHP_ProjectList::Instantiate(GITPHP_CONFIGDIR . 'projects.conf.php', false);
        }
    }

    public function run()
    {
        GitPHP_Log::GetInstance()->SetStartTime(GITPHP_START_TIME);
        GitPHP_Log::GetInstance()->SetStartMemory(GITPHP_START_MEM);

        try {
            GitPHP_Log::GetInstance()->timerStart();
            $controller = $this->getController(isset($_GET['a']) ? $_GET['a'] : null);
            GitPHP_Log::GetInstance()->timerStop('getController');
            if ($controller) {
                GitPHP_Log::GetInstance()->timerStart();
                $controller->RenderHeaders();
                GitPHP_Log::GetInstance()->timerStop('RenderHeaders');
                GitPHP_Log::GetInstance()->timerStart();
                $controller->Render();
                GitPHP_Log::GetInstance()->timerStop('Render');
            }
        } catch (Exception $e) {
            trigger_error($e);
            $this->showExceptionMessage($e);
        }
    }

    protected function showExceptionMessage(Exception $e)
    {
        try {
            $controller = new GitPHP_Controller_Message();
            $controller->SetParam('message', $e->getMessage());
            if ($e instanceof GitPHP_MessageException) {
                $controller->SetParam('error', $e->Error);
                $controller->SetParam('statuscode', $e->StatusCode);
            } else {
                $controller->SetParam('error', true);
            }
            $controller->RenderHeaders();
            $controller->Render();
        } catch (Exception $e) {
            if (GitPHP_Config::GetInstance()->GetValue('debug', false)) throw $e;
        }
    }

    protected function getController($action)
    {
        $controller = null;

        switch ($action) {
            case 'search':
            case 'search_json':
                $controller = new GitPHP_Controller_Search();
                break;

            case 'searchtext':
                $controller = new GitPHP_Controller_SearchText();
                break;

            case 'commitdiff':
            case 'commitdiff_plain':
                $controller = new GitPHP_Controller_Commitdiff();
                if ($action === 'commitdiff_plain') $controller->SetParam('plain', true);
                break;

            case 'branchdiff':
            case 'branchdiff_plain':
                $controller = new GitPHP_Controller_Branchdiff();
                if ($action === 'branchdiff_plain') $controller->SetParam('plain', true);
                break;

            case 'blobdiff':
            case 'blobdiff_plain':
                $controller = new GitPHP_Controller_Blobdiff();
                if ($action === 'blobdiff_plain') $controller->SetParam('plain', true);
                break;

            case 'history':
                $controller = new GitPHP_Controller_History();
                break;

            case 'shortlog':
            case 'log':
            case 'branchlog':
                $controller = new GitPHP_Controller_Log();
                if ($action === 'shortlog') $controller->SetParam('short', true);
                if ($action === 'branchlog') {
                    $controller->SetParam('branchlog', true);
                }
                break;

            case 'snapshot':
                $controller = new GitPHP_Controller_Snapshot();
                break;

            case 'tree':
                $controller = new GitPHP_Controller_Tree();
                break;

            case 'tag':
                $controller = new GitPHP_Controller_Tag();
                break;

            case 'tags':
                $controller = new GitPHP_Controller_Tags();
                break;

            case 'heads':
                $controller = new GitPHP_Controller_Heads();
                break;

            case 'blame':
                $controller = new GitPHP_Controller_Blame();
                break;

            case 'blob':
            case 'blob_plain':
                $controller = new GitPHP_Controller_Blob();
                if ($action === 'blob_plain') $controller->SetParam('plain', true);
                break;

            case 'atom':
            case 'rss':
                $controller = new GitPHP_Controller_Feed();
                if ($action == 'rss') $controller->SetParam('format', GITPHP_FEED_FORMAT_RSS);
                else if ($action == 'atom') $controller->SetParam('format', GITPHP_FEED_FORMAT_ATOM);
                break;

            case 'commit':
                $controller = new GitPHP_Controller_Commit();
                break;

            case 'summary':
                $controller = new GitPHP_Controller_Project();
                break;

            case 'project_index':
                $controller = new GitPHP_Controller_ProjectList();
                $controller->SetParam('txt', true);
                break;

            case 'opml':
                $controller = new GitPHP_Controller_ProjectList();
                $controller->SetParam('opml', true);
                break;

            case 'login':
                $controller = new GitPHP_Controller_Login();
                break;

            case 'logout':
                $controller = new GitPHP_Controller_Logout();
                break;

            case 'save_comment':
            case 'save_comment_sbs':
            case 'delete_comment':
            case 'delete_comment_sbs':
            case 'get_review':
            case 'get_comments':
            case 'get_comments_sbs':
            case 'set_review_status':
            case 'get_unfinished_review':
            case 'delete_all_draft_comments':
                $controller = new GitPHP_Controller_Comment();
                break;

            case 'reviews':
                $controller = new GitPHP_Controller_Review();
                break;

            case 'gitosis':
                $section = empty($_GET['section']) ? GitPHP_Controller_GitosisBase::DEFAULT_SECTION : $_GET['section'];
                if (!in_array($section, GitPHP_Controller_GitosisBase::getSections())) exit(1);

                $ucsection = ucfirst($section);
                $class_name = 'GitPHP_Controller_Gitosis' . $ucsection;
                $controller = new $class_name();
                break;

            case 'check_session':
                $controller = new GitPHP_Controller_CheckSession();
                break;

            default:
                if (in_array($action, GitPHP_Controller_Git::SUPPORTED_ACTIONS)) {
                    $controller = new GitPHP_Controller_Git();
                } else if (isset($_GET['p'])) {
                    $controller = new GitPHP_Controller_Project();
                } else {
                    $controller = new GitPHP_Controller_ProjectList();
                }
        }
        GitPHP_Log::GetInstance()->Log('controller', get_class($controller));
        GitPHP_Log::GetInstance()->Log('REQUEST_URI', $_SERVER['REQUEST_URI']);
        GitPHP_Log::GetInstance()->Log('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
        GitPHP_Log::GetInstance()->Log('phpversion', phpversion());
        return $controller;
    }

    public static function getUrl($controller, array $params = [])
    {
        return '/index.php?' . http_build_query(['a' => $controller] + $params);
    }
}

