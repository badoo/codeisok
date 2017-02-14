#!/usr/bin/php
<?php
require_once(dirname(__FILE__).'/bootstrap.php');

class SSH_Serve
{
    const COMMANDS_READONLY = [
        'git-upload-pack',
        'git upload-pack',
        'git-upload-archive',
        'git upload-archive',
    ];

    const COMMANDS_WRITE = [
        'git-receive-pack',
        'git receive-pack',
    ];

    protected $user = '';
    protected $command = '';
    protected $argument = '';

    public function init()
    {
        $original_command = getenv('SSH_ORIGINAL_COMMAND');
        if ($_SERVER['argc'] > 0) {
            $this->user = $_SERVER['argv'][1];
            list($cmd, $arg) = explode(' ', $original_command);
            $this->argument = trim($arg, "'");
            $project_root = GitPHP_Config::GetInstance()->getValue(GitPHP_Config::PROJECT_ROOT);
            if (strpos($this->argument, $project_root) === false) {
                $this->argument = $project_root . $this->argument;
            }
            if (!file_exists($this->argument)) {
                $this->error('Repo can\'t be found by the path given: ' . $this->argument);
            }
            $this->command = $cmd;
        } else {
            $this->error('SSH Serve requires user name.');
        }
    }

    public function run()
    {
        passthru('git-shell -c "' . $this->command . ' ' . escapeshellarg($this->argument) . '"');
        file_put_contents('out.txt', var_export([$this->command, $this->argument], 1), FILE_APPEND);
    }

    function error($message)
    {
        throw new Exception($message . PHP_EOL);
    }
}

$Application = new GitPHP_Application();
$Application->init();

$Serve = new SSH_Serve();
$Serve->init();
$Serve->run();

