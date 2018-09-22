<?php

define('GITPHP_START_TIME', microtime(true));
define('GITPHP_START_MEM', memory_get_usage());

require_once __DIR__ . '/bootstrap.php';

define('DO_NOT_USE_ERROR_HANDLER', 1);

$Application = new \GitPHP\Application();

\GitPHP\Log::GetInstance()->timerStart();
$Application->init();
\GitPHP\Log::GetInstance()->timerStop('GitPHP\Application::init()', 1);

\GitPHP\Log::GetInstance()->timerStart();
$Application->run();
\GitPHP\Log::GetInstance()->timerStop('GitPHP\Application::run()', 1);

\GitPHP\Log::GetInstance()->Log('debug', \GitPHP\Config::GetInstance()->GetValue('debug', false));

/* StatSlow ;) */
\GitPHP\Log::GetInstance()->printHtmlHeader();
\GitPHP\Log::GetInstance()->printHtml();
\GitPHP\Log::GetInstance()->printHtmlFooter();
