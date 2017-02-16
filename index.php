<?php
define('GITPHP_START_TIME', microtime(true));
define('GITPHP_START_MEM', memory_get_usage());

require_once __DIR__ . '/bootstrap.php';

define('DO_NOT_USE_ERROR_HANDLER', 1);

$Application = new GitPHP_Application();

GitPHP_Log::GetInstance()->timerStart();
$Application->init();
GitPHP_Log::GetInstance()->timerStop('GitPHP_Application::init()', 1);

GitPHP_Log::GetInstance()->timerStart();
$Application->run();
GitPHP_Log::GetInstance()->timerStop('GitPHP_Application::run()', 1);

GitPHP_Log::GetInstance()->Log('debug', GitPHP_Config::GetInstance()->GetValue('debug', false));

/* StatSlow ;) */
GitPHP_Log::GetInstance()->printHtmlHeader();
GitPHP_Log::GetInstance()->printHtml();
GitPHP_Log::GetInstance()->printHtmlFooter();


