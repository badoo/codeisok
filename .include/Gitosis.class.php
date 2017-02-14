<?php

class GitPHP_Gitosis
{
    const HOME = GitPHP_Config::GIT_HOME;
    const KEYFILE = '.ssh/authorized_keys';

    public static function addKey($user, $key)
    {
        $key = trim($key);
        $ssh_command = 'command="./ssh_serve.php ' . $user
            . '",no-port-forwarding,no-agent-forwarding,no-X11-forwarding,no-pty ' . $key . PHP_EOL;
        return (false !== file_put_contents(self::HOME . self::KEYFILE, $ssh_command, FILE_APPEND));
    }
}
