#!/usr/bin/php
<?php

$options = getopt("u:");

if (!empty($options) and !empty($options['u'])) {
    $user = $options['u'];
    $res = run("id " . $user . " 2>&1");
    if ($res['code'] !== 0) {
        $res = run('useradd -d /home/' . $user . ' -m ' . $user . ' -s /bin/bash');
        if ($res['code'] === 0) {
            echo "Provide ssh-key:\n";
            $ssh_key = fgets(STDIN);
            $ssh_path = '/home/' . $user . '/.ssh';
            if (mkdir($ssh_path, 0700)) {
                chown($ssh_path, $user);
                if (file_put_contents($ssh_path . '/authorized_keys', $ssh_key, FILE_APPEND) !== false) {
                    chmod($ssh_path . '/authorized_keys', 0600);
                    chown($ssh_path . '/authorized_keys', $user);
                    $res = run('usermod ' . $user . ' -G ubuntu');
                    if ($res['code'] === 0) {
                        echo "Done.\n";
                    } else {
                        echo "Can't add group to user.\n";
                    }
                } else {
                    echo "Can't add key to authorized_keys file.\n";
                }
            } else {
                echo "Can't create .ssh dir in user home folder.\n";
            }
        } else {
            echo "Can't do useradd.\n";
        }
    } else {
        echo "User already exists.\n";    
    }
} else {
    usage();
}

function usage() {
    echo "Usage: " . basename(__FILE__) . " -u<username>\n";
}

function run($cmd) {
    $out = [];
    $code = 0;
    exec($cmd, $out, $code);
    return ['code' => $code, 'output' => implode("\n", $out)];
}
