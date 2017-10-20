#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/bootstrap.php');

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

    const ARG_REGEXP = "#^'/*(?P<path>[a-zA-Z0-9][a-zA-Z0-9@._-]*(/[a-zA-Z0-9][a-zA-Z0-9@._-]*)*)'$#";

    protected $user = '';
    protected $command = '';
    protected $full_path = '';
    protected $repository = '';

    public function init()
    {
        $original_command = getenv('SSH_ORIGINAL_COMMAND');
        if ($_SERVER['argc'] > 0) {
            $this->user = $_SERVER['argv'][1];
            @list($cmd, $arg) = explode(' ', $original_command);
            if (preg_match(self::ARG_REGEXP, $arg, $m)) {
                if (!empty($cmd) && !empty($arg) && (in_array($cmd, self::COMMANDS_WRITE) || in_array($cmd, self::COMMANDS_READONLY))) {
                    $this->full_path = $this->repository = $m['path'];

                    $project_root = GitPHP_Config::GetInstance()->getValue(GitPHP_Config::PROJECT_ROOT);

                    if (strpos($this->full_path, $project_root) === false) {
                        $this->full_path = $project_root . $this->full_path;
                    }

                    if (!file_exists($this->full_path)) {
                        if (strpos($this->full_path, '.git') === false) {
                            $this->full_path .= '.git';
                            $this->repository .= '.git';
                            if (!file_exists($this->full_path)) {
                                $this->error('Repo can\'t be found by the path given.');
                            }
                        } else {
                            $this->error('Repo can\'t be found by the path given.');
                        }
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
        if ($this->isNormalUser($ModelGitosis, $this->user) || $this->isRestrictedRepository($ModelGitosis, $this->repository)) {
            $access = $ModelGitosis->getUserAccessToRepository($this->user, $this->repository);
        } else {
            $access = ['mode' => 'writable'];
        }
        if (!empty($access)) {
            if (in_array($this->command, self::COMMANDS_WRITE) && $access['mode'] !== 'writable') {
                $this->error('You don\' have write access to repo.');
            }
            if (!extension_loaded('pcntl') && !dl('pcntl.so')) {
                // we've seen some strange git behaviour without using pcntl_exec
                // nevertheless I've saved this part for back-compatibility with old php config
                trigger_error('cannot load pcntl extension');
                $escaped_user = escapeshellarg($this->user);
                $escaped_repo = escapeshellarg($this->repository);
                passthru(
                    'GITOSIS_USER=' . $escaped_user . ' GITOSIS_REPO=' . $escaped_repo . ' git-shell -c "' . $this->command . ' ' . escapeshellarg($this->full_path) . '"'
                );
            } else {
                // we need this for hooks and back-compatibility with gitosis
                putenv('GITOSIS_USER=' . $this->user);
                putenv('GITOSIS_REPO=' . $this->repository);
                pcntl_exec(
                    '/usr/bin/git-shell',
                    ['-c', $this->command . ' ' . escapeshellarg($this->full_path)]
                );
            }
        } else {
            $this->error("You don't have rights to access the repo.");
        }
        //file_put_contents('out.txt', var_export([$this->user, $this->command, $this->argument, $access], 1), FILE_APPEND);
    }

    protected function isRestrictedRepository(Model_Gitosis $ModelGitosis, $repository)
    {
        $repository_info = $ModelGitosis->getRepositoryByProject($repository);
        return $repository_info['restricted'] == 'Yes';
    }

    protected function isNormalUser(Model_Gitosis $ModelGitosis, $username)
    {
        $user = $ModelGitosis->getUserByUsername($username);
        return $user['access_mode'] == \GitPHP\Controller\GitosisUsers::ACCESS_MODE_NORMAL;
    }

    protected function error($message)
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
