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
        $this->user = $_SERVER['argv'][1] ?? '';
        if (empty($this->user)) {
            $this->error('wrong ssh_serve script usage. Expected username as the only argument');
        }

        @list($command, $arguments) = explode(' ', $original_command);
        if (!preg_match(self::ARG_REGEXP, $arguments, $matches) || !isset($matches['path'])) {
            $this->error("command looks unsafe: {$command} {$arguments}");
        }
        $this->full_path = $this->repository = $matches['path'];

        if (empty($command) || empty($arguments) || $this->commandIsNotSupported($command)) {
            $this->error("command is not supported: {$command} {$arguments}");
        }

        $project_root = \GitPHP\Config::GetInstance()->getValue(\GitPHP\Config::PROJECT_ROOT);

        if (strpos($this->full_path, $project_root) === false) {
            $this->full_path = $project_root . $this->full_path;
        }

        if (!file_exists($this->full_path)) {
            if (strpos($this->full_path, '.git') === false) {
                $this->full_path .= '.git';
                $this->repository .= '.git';
                if (!file_exists($this->full_path)) {
                    $this->error('repo can\'t be found by the path given.');
                }
            } else {
                $this->error('repo can\'t be found by the path given.');
            }
        }

        $this->command = $command;
    }

    public function run()
    {
        $ModelGitosis = new Model_Gitosis();
        $global_mode = $this->getUserGlobalAccessMode($ModelGitosis, $this->user);
        if ($global_mode === 'readonly' && $this->isWriteCommand($this->command)) {
            // it's not enough to have readonly global mode for write commands
            // so let's assume that we don't have global mode at all
            $global_mode = false;
        }

        if (!$global_mode || $this->isRestrictedRepository($ModelGitosis, $this->repository)) {
            $access = $ModelGitosis->getUserAccessToRepository($this->user, $this->repository);
        } else {
            $access = $global_mode;
        }

        if (!empty($access)) {
            if ($this->isWriteCommand($this->command) && $access !== 'writable') {
                $this->error('You don\' have write access to repo.');
            }
            if (!function_exists('pcntl_exec') && !extension_loaded('pcntl') && !dl('pcntl.so')) {
                // we've seen some strange git behaviour without using pcntl_exec
                // nevertheless I've saved this part for back-compatibility with old php config
                trigger_error('cannot load pcntl extension');
                $escaped_user = escapeshellarg($this->user);
                $escaped_repo = escapeshellarg($this->repository);

                $command = implode(' ', [
                    'GITOSIS_USER=' . $escaped_user,
                    'GITOSIS_REPO=' . $escaped_repo,
                    'git-shell -c "' . $this->command . ' ' . escapeshellarg($this->full_path) . '"'
                ]);
                passthru(
                    $command
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
    }

    protected function commandIsNotSupported($command)
    {
        return !($this->isWriteCommand($command) || $this->isReadCommand($command));
    }

    protected function isWriteCommand($command)
    {
        return in_array($command, self::COMMANDS_WRITE, true);
    }

    protected function isReadCommand($command)
    {
        return in_array($command, self::COMMANDS_READONLY, true);
    }

    protected function isRestrictedRepository(Model_Gitosis $ModelGitosis, $repository)
    {
        $repository_info = $ModelGitosis->getRepositoryByProject($repository);
        return $repository_info['restricted'] === 'Yes';
    }

    protected function getUserGlobalAccessMode(Model_Gitosis $Gitosis, $username)
    {
        $user = $Gitosis->getUserByUsername($username);
        if ($user['access_mode'] === \GitPHP\Controller\GitosisUsers::ACCESS_MODE_ALLOW_ALL) {
            return 'writable';
        }

        if ($user['access_mode'] === \GitPHP\Controller\GitosisUsers::ACCESS_MODE_ALLOW_ALL_RO) {
            return 'readonly';
        }

        return false;
    }

    protected function error($message)
    {
        fwrite(STDERR, '[ERROR]: ' . $message . PHP_EOL);
        exit(1);
    }
}

$Application = new GitPHP\Application();
$Application->init();

$Serve = new SSH_Serve();
$Serve->init();
$Serve->run();
