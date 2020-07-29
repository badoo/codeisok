<?php

namespace GitPHP;

class Gitosis
{
    const CONFIG_AUTHORIZED_KEYS_FILE = 'authorized_keys_file';

    protected static $key_file_location;

    public static function getAuthorizedKeysFile()
    {
        if (!isset(self::$key_file_location)) {
            $key_file = \GitPHP\Config::GetInstance()->GetValue(self::CONFIG_AUTHORIZED_KEYS_FILE, '.ssh/authorized_keys');
            self::$key_file_location = \GitPHP\Config::GIT_HOME . $key_file;
        }
        return self::$key_file_location;
    }

    public static function addKey($user, $key)
    {
        $ssh_command = self::formatKeyString('.', $user, $key) . PHP_EOL;
        return (false !== file_put_contents(self::getAuthorizedKeysFile(), $ssh_command, FILE_APPEND));
    }

    public static function formatKeyString($base_dir, $user, $key)
    {
        $key = trim($key);
        $ssh_command = 'command="' . $base_dir . '/ssh_serve.php ' . $user
            . '",no-port-forwarding,no-agent-forwarding,no-X11-forwarding,no-pty ' . $key;
        return $ssh_command;
    }
}
