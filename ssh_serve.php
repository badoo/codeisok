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

    const COMMAND_REGEX = "#^([a-z-\s]+)\s'/*(?P<path>[a-zA-Z0-9][a-zA-Z0-9@._-]*(/[a-zA-Z0-9][a-zA-Z0-9@._-]*)*)'$#";

    protected $user = '';
    protected $command = '';
    protected $argument = '';

    public function init()
    {
        $original_command = getenv('SSH_ORIGINAL_COMMAND');
        if ($_SERVER['argc'] > 0) {
            $this->user = $_SERVER['argv'][1];
            if (preg_match(self::COMMAND_REGEX, $original_command, $m)) {
                @list($cmd, $arg) = explode(' ', $original_command);
                if (!empty($cmd) && !empty($arg) && (in_array($cmd, self::COMMANDS_WRITE) || in_array($cmd, self::COMMANDS_READONLY))) {
                    $this->argument = trim($arg, "'");

                    $project_root = GitPHP_Config::GetInstance()->getValue(GitPHP_Config::PROJECT_ROOT);

                    if (strpos($this->argument, $project_root) === false) {
                        $this->argument = $project_root . $this->argument;
                    }

                    if (!file_exists($this->argument)) {
                        $this->error('Repo can\'t be found by the path given.');
                    }

                    $this->command = $cmd;
                } else {
                    $this->error('Command is not supported!: ' . $cmd . ' ' . $arg);
                }
            } else {
                $this->error("Command looks unsafe.");
            }
        } else {
            $this->error('SSH Serve requires user name.');
        }
    }

    public function run()
    {
        $ModelGitosis = new Model_Gitosis();
        $access = $ModelGitosis->getUserAccessToRepository($this->user, basename($this->argument));
        if (!empty($access)) {
            if (in_array($this->command, self::COMMANDS_WRITE) && $access['mode'] !== 'writable') {
                $this->error('You don\' have write access to repo.');
            }
            passthru('git-shell -c "' . $this->command . ' ' . escapeshellarg($this->argument) . '"');
        } else {
            $this->error("You don't have rights to access the repo.");
        }
        //file_put_contents('out.txt', var_export([$this->user, $this->command, $this->argument, $access], 1), FILE_APPEND);
    }

    function error($message)
    {
        fwrite(STDERR, '[ERROR]: ' . $message . PHP_EOL);
        exit(1);
    }
}

$Application = new GitPHP_Application();
$Application->init();

$Serve = new SSH_Serve();
$Serve->init();
$Serve->run();

